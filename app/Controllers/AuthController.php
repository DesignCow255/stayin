<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\BusinessException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;
use App\Middleware\SessionTarget;
use App\Services\AuthService;
use App\Services\AccountService;

final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        return $this->view('auth/login', [
            'metaTitle' => 'Sign in · StayIn',
            'metaDescription' => 'Sign in to your StayIn account.',
        ]);
    }

    public function login(Request $request): Response
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ])->validated();

        if (AuthService::attempt((string) $data['email'], (string) $data['password'])) {
            Session::flash('status', ['type' => 'success', 'message' => 'Welcome back!']);
            $home = AuthService::home();
            $intended = SessionTarget::pullIntended($home);

            if (!$this->isAllowedLanding($intended, $home)) {
                $intended = $home;
            }

            return $this->redirect($intended);
        }

        Session::flashErrors(['email' => 'Those credentials do not match our records.']);
        Session::flashInput($request->only(['email']));

        return $this->redirect('/login', 303);
    }

    public function showRegister(Request $request): Response
    {
        return $this->view('auth/register', [
            'metaTitle' => 'Create account · StayIn',
            'metaDescription' => 'Create a StayIn guest or host account.',
        ]);
    }

    public function register(Request $request): Response
    {
        $validated = $request->validate([
            'first_name' => 'required|min:2|max:100',
            'last_name' => 'required|min:2|max:100',
            'email' => 'required|email|max:190',
            'phone' => 'phone',
            'password' => 'required|min:10|max:72',
            'password_confirmation' => 'required',
            'role' => 'required|in:guest,host',
        ])->validated();

        $role = (string) $validated['role'];
        if (!in_array($role, ['guest', 'host'], true)) {
            throw new BusinessException('Only guest and host accounts can be created from this form.');
        }

        if ((string) $validated['password'] !== (string) $validated['password_confirmation']) {
            return $this->failRegister(['password_confirmation' => 'Password confirmation does not match.'], $request);
        }

        $email = mb_strtolower(trim((string) $validated['email']));
        if (User::emailExists($email)) {
            return $this->failRegister(['email' => 'An account with this email already exists.'], $request);
        }

        $phone = trim((string) ($validated['phone'] ?? ''));
        if ($phone !== '' && User::phoneExists($phone)) {
            return $this->failRegister(['phone' => 'An account with this phone number already exists.'], $request);
        }

        $userId = User::create([
            'first_name' => trim((string) $validated['first_name']),
            'last_name' => trim((string) $validated['last_name']),
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'password_hash' => AuthService::hash((string) $validated['password']),
            'role' => $role,
        ]);

        AuthService::login($userId);
        AccountService::verification($userId);

        Session::flash('status', ['type' => 'success', 'message' => 'Account created. Please verify your email address to continue.']);

        return $this->redirect('/verify-email');
    }

    public function logout(Request $request): Response
    {
        AuthService::logout();
        Session::flash('status', ['type' => 'success', 'message' => 'You have signed out.']);

        return $this->redirect('/');
    }

    public function showForgot(Request $request): Response
    {
        return $this->view('auth/forgot', ['metaTitle' => 'Reset password · StayIn', 'metaDescription' => '']);
    }

    public function sendReset(Request $request): Response
    {
        $request->validate(['email'=>'required|email']);
        AccountService::resetLink($request->string('email'));
        Session::flash('status', ['message'=>'If the account is eligible, a reset link has been queued.']);
        return $this->redirect('/forgot-password');
    }

    public function showReset(Request $request): Response
    {
        return $this->view('auth/reset', ['token'=>$request->routeParam('token'), 'metaTitle' => 'Choose a new password · StayIn', 'metaDescription' => '']);
    }

    public function reset(Request $request): Response
    {
        if ($request->string('password') !== $request->string('password_confirmation')) throw new \App\Core\BusinessException('Passwords do not match.');
        AccountService::reset($request->string('token'),$request->string('password'));
        AuthService::logout();
        Session::flash('status',['message'=>'Password updated. Please sign in.']);
        return $this->redirect('/login');
    }

    public function verifyNotice(Request $request): Response
    {
        $user = AuthService::user();
        return $this->view('auth/verify', [
            'metaTitle' => 'Verify email · StayIn',
            'metaDescription' => '',
            'email' => is_array($user) ? (string) ($user['email'] ?? '') : null,
        ]);
    }

    public function resendVerification(Request $request): Response
    {
        if (AuthService::id()) AccountService::verification(AuthService::id());
        Session::flash('status',['message'=>'Verification email queued.']);
        return $this->back();
    }

    public function verify(Request $request): Response
    {
        AccountService::verify((string)$request->routeParam('token'));
        Session::flash('status',['type'=>'success','message'=>'Email verified successfully.']);
        return $this->redirect(AuthService::check() ? AuthService::home() : '/login');
    }

    /**
     * @param array<string, string> $errors
     */
    private function failRegister(array $errors, Request $request): Response
    {
        Session::flashErrors($errors);
        Session::flashInput($request->only(['first_name', 'last_name', 'email', 'phone', 'role']));
        return $this->redirect('/register', 303);
    }

    private function isAllowedLanding(string $path, string $home): bool
    {
        if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return false;
        }

        if (str_starts_with($path, '/admin')) {
            return $home === '/admin';
        }

        if (str_starts_with($path, '/host')) {
            return $home === '/host';
        }

        if (str_starts_with($path, '/guest')) {
            return $home === '/guest';
        }

        return true;
    }
}
