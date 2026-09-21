<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BusinessException;
use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Services\AuthService;
use App\Services\FinanceService;
use App\Services\Gate;

final class ControlAccountController extends Controller
{
    private function requireSuperAdmin(): void
    {
        if (!Gate::anyRole(['super_admin'])) {
            throw HttpException::forbidden(
                'Super administrator access is required.'
            );
        }
    }

    public function index(Request $request): Response
    {
        $this->requireSuperAdmin();

        $accounts = Database::select(
            "SELECT
                id,
                first_name,
                last_name,
                email,
                role,
                status,
                email_verified_at,
                last_login_at,
                created_at
             FROM users
             WHERE role IN ('admin', 'super_admin')
             ORDER BY
                CASE WHEN role = 'super_admin' THEN 0 ELSE 1 END,
                id ASC"
        );

        return $this->view('control/accounts/index', [
            'metaTitle' => 'Administrative accounts · StayIn Control',
            'accounts' => $accounts,
            'robots' => 'noindex,nofollow,noarchive',
        ]);
    }

    public function create(Request $request): Response
    {
        $this->requireSuperAdmin();

        return $this->view('control/accounts/create', [
            'metaTitle' => 'Create administrative account · StayIn Control',
            'robots' => 'noindex,nofollow,noarchive',
        ]);
    }

    public function store(Request $request): Response
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => 'required|email|max:190',
            'role' => 'required|in:admin,super_admin',
            'password' => 'required|min:12|max:255',
            'password_confirmation' => 'required|min:12|max:255',
        ])->validated();

        $firstName = trim((string) $validated['first_name']);
        $lastName = trim((string) $validated['last_name']);
        $email = mb_strtolower(trim((string) $validated['email']));
        $role = (string) $validated['role'];
        $password = (string) $validated['password'];
        $confirmation = (string) $validated['password_confirmation'];

        if ($password !== $confirmation) {
            throw new BusinessException('Passwords do not match.');
        }

        if (
            strlen($password) < 12 ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[0-9]/', $password)
        ) {
            throw new BusinessException(
                'Password must contain at least 12 characters with uppercase, lowercase and a number.'
            );
        }

        if (User::emailExists($email)) {
            throw new BusinessException(
                'An account with that email already exists.'
            );
        }

        $actor = AuthService::id();

        $id = Database::transaction(
            static function () use (
                $firstName,
                $lastName,
                $email,
                $role,
                $password,
                $actor
            ): int {
                $id = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password_hash' => AuthService::hash($password),
                    'role' => $role,
                    'status' => 'active',
                    'email_verified_at' => gmdate('Y-m-d H:i:s'),
                ]);

                FinanceService::audit(
                    $actor,
                    'privileged_account.created',
                    'users',
                    $id,
                    [
                        'role' => $role,
                    ]
                );

                return $id;
            }
        );

        unset($password, $confirmation, $validated);

        Session::flash('status', [
            'message' => sprintf(
                '%s account created successfully.',
                $role === 'super_admin' ? 'Super administrator' : 'Administrator'
            ),
        ]);

        return $this->redirect('/control/accounts', 303);
    }
}
