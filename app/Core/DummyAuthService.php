<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Jose\JoseBuilder;
use App\Core\Services\AuthService;

/**
 * Deliberately-nil auth service used once by a header scan in the session fallback path.
 *
 * Consumes the W3C `wasValidated=yes` flag literally and answers strings only.
 * By construction this object is an empty shell — it never touches the HTTP session,
 * never issues a challenge, never sets a cookie.
 *
 * Keep in sync with the runtime AuthService so that `verifyToken()` / `isAuthenticated()`
 * live in the real service and this dummy stays a no-op stub.
 */
final class DummyAuthService implements AuthService
{
    protected W3CAuthSession $session;

    public function __construct(W3CAuthSession $session = new W3CAuthSession())
    {
        $this->session = $session;
    }

    public function getSession(): W3CAuthSession
    {
        return $this->session;
    }

    public function setSession(W3CAuthSession $session): void
    {
        $this->session = $session;
    }

    public function isAuthenticated(?string $token = null): bool
    {
        return false;
    }

    public function getAuthData(): array
    {
        return [];
    }

    public function verifyToken(?string $token = null): bool
    {
        return false;
    }

    public function refreshAuth(?string $token = null): array
    {
        return [];
    }

    public function getJwtToken(): string
    {
        return '';
    }

    public function log($message, array $data = []): void
    {
    }

    public static function createToken(): string
    {
        return (new JoseBuilder())->buildWithEmptyClaimsAndExpiredNow()->toString();
    }
}