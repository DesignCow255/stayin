<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\Gate;

final class ControlAuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        if (AuthService::check()) {
            if (Gate::allows('admin.access')) {
                return $this->redirect('/admin');
            }

            return $this->redirect('/');
        }

        return $this->view('control/login', [
            'metaTitle' => 'Administrative access · StayIn',
            'metaDescription' => '',
            'robots' => 'noindex,nofollow,noarchive',
        ]);
    }

    public function login(Request $request): Response
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ])->validated();

        if (AuthService::attemptStaff(
            (string) $data['email'],
            (string) $data['password']
        )) {
            Session::flash('status', [
                'type' => 'success',
                'message' => 'Administrative session established.',
            ]);

            return $this->redirect('/admin', 303);
        }

        Session::flashErrors([
            'email' => 'Those credentials do not match our records.',
        ]);

        return $this->redirect('/control/login', 303);
    }

    public function logout(Request $request): Response
    {
        AuthService::logout();

        Session::flash('status', [
            'type' => 'success',
            'message' => 'Administrative session ended.',
        ]);

        return $this->redirect('/control/login', 303);
    }
}
