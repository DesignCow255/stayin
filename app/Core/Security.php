<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP security hardening. Headers are applied centrally so every response
 * (including error responses) is protected.
 */
final class Security
{
    /**
     * Apply security headers. CSP is intentionally strict but allows the
     * specific third-party origins needed by payments/OAuth/maps once configured.
     */
    public static function applyHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Permissions-Policy: geolocation=(self), microphone=(), camera=(), payment=(self), usb=()');
        header('X-Permitted-Cross-Domain-Policies: none');

        if (Config::bool('security.force_https', false) || self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        if (Config::bool('security.csp_enabled', true)) {
            header('Content-Security-Policy: ' . self::contentSecurityPolicy());
        }
    }

    public static function contentSecurityPolicy(): string
    {
        $extraImg = Config::string('security.csp_extra_img_src', 'https:');
        $imgSrc = "'self' data: blob:";
        if ($extraImg !== '') {
            $imgSrc .= ' ' . $extraImg;
        }

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "img-src " . $imgSrc,
            "font-src 'self' data:",
            "style-src 'self' 'unsafe-inline'",
            "script-src 'self'",
            "connect-src 'self'",
            "manifest-src 'self'",
            "worker-src 'self'",
        ];

        // Allow explicitly configured third-party origins (OAuth, maps, analytics).
        $connect = Config::array('security.csp_connect_extra', []);
        if ($connect !== []) {
            $directives[array_search("connect-src 'self'", $directives, true) ?: 0] = "connect-src 'self' " . implode(' ', $connect);
        }
        $frame = Config::array('security.csp_frame_extra', []);
        if ($frame !== []) {
            $directives[] = 'frame-src ' . implode(' ', $frame);
        }
        $script = Config::array('security.csp_script_extra', []);
        if ($script !== []) {
            $directives[array_search("script-src 'self'", $directives, true) ?: 0] = "script-src 'self' " . implode(' ', $script);
        }

        return implode('; ', $directives) . ';';
    }

    public static function isHttps(): bool
    {
        if (($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    /**
     * Constant-time string comparison helper.
     */
    public static function equals(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }

    public static function randomToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }
}