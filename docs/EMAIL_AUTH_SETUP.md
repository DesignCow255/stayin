# StayIn Email Authentication Setup

StayIn now requires verified email for guest, host, and admin dashboards. Registration signs the user in only to the verification flow; protected dashboards remain blocked until verification succeeds.

## Production SMTP
Set these values in `.env` on the production server (never commit real credentials):

```env
APP_ENV=production
APP_URL=https://stayin.co.tz
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@stayin.co.tz
MAIL_FROM_NAME=StayIn
MAIL_MAX_ATTEMPTS=3
```

Configure SPF, DKIM and DMARC for the sending domain with the selected mail provider.

## Queue worker
Verification and password-reset mail is queued in `email_queue`. Run the application's mail worker regularly. The worker method is `App\Services\MailService::work()`. In local/log mode messages are captured under `storage/private/mail`; in production SMTP mode they are delivered over authenticated SMTP/TLS.

## Verification behavior
- Verification tokens are 256-bit random values; only SHA-256 hashes are stored.
- Resending deletes unused older verification tokens.
- Verification links expire according to `auth.email_verification_ttl_hours` (48 hours by default).
- Successful verification invalidates all outstanding links for that user.
- Guest, host and admin dashboard groups require `auth` + `verified`.
- Password reset tokens are single-use and password changes clear recorded user sessions.

## Required PHP extensions
The project requires PHP 8.3+, `pdo_mysql`, `mbstring`, `openssl`, `json`, and `fileinfo`.

## Smoke test
1. Apply migrations, including `001_marketplace_completion.sql`.
2. Register a new guest with a real email address.
3. Confirm `/verify-email` is shown instead of the guest dashboard.
4. Run the mail worker and confirm receipt of the verification email.
5. Open the verification link once; confirm it succeeds.
6. Open the same link again; confirm it is rejected as invalid/expired.
7. Sign in and confirm the dashboard is accessible.
8. Test forgot/reset password and confirm the old reset link cannot be reused.
9. Test a new unverified host and confirm `/host` redirects to verification.
