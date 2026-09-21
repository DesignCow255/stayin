# StayIn Deployment Checklist

Legend: `[x] Verified in this audit`, `[ ] Pending`, `[!] Blocked / needs approval`

## Git

- [x] Current branch identified: `master`.
- [x] Recent commits inspected.
- [x] Remote configuration inspected: no remote configured.
- [!] Review extensive pre-existing uncommitted/untracked changes before release.
- [x] Sensitive backup/session artifacts quarantined outside deployable tree; `composer release:check` prevents reintroduction.

## Environment Variables

- [x] `.env.example` expanded for discovered env vars.
- [ ] Production `.env` created outside version control.
- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] `APP_URL` and `ASSET_URL` match production domain/base path.
- [ ] `APP_KEY` generated and stored securely.
- [ ] `SESSION_SECURE=true` on HTTPS.
- [ ] `TRUST_PROXY_HEADERS=true` only behind trusted proxy/load balancer.

## Secrets

- [x] `.env` ignored by Git.
- [x] Local backups/session files segregated to a private quarantine outside the repository.
- [ ] Rotate any credentials that may have appeared in local dumps if they were ever real.
- [ ] Store provider secrets in the production secret manager or protected server env.

## Database

- [x] Local database connection smoke test passed.
- [x] Local migration dry-run reported `pending: 0`.
- [ ] Production database backup taken before migration.
- [ ] Production DB user has least-privilege application credentials.
- [ ] Confirm MySQL version compatibility.

## Migrations

- [x] Migration runner inspected; no destructive reset command found.
- [x] `php cli/stayin.php migrate --dry-run` succeeded locally.
- [ ] Run production migration through reviewed deployment window.
- [ ] Capture migration logs and rollback plan.

## Dependencies

- [x] `composer validate` passed.
- [x] `composer check-platform-reqs` passed locally.
- [x] `composer audit` found no packages to audit.
- [ ] Run `composer install --no-dev --optimize-autoloader` on clean release environment.

## Production Build

- [x] No npm/build step detected.
- [x] PHP changed-file syntax checks passed.
- [x] Public HTTP smoke passed locally.
- [ ] Re-run full PHP lint in CI with longer timeout.

## Domain / DNS / SSL

- [ ] DNS points to production server.
- [ ] SSL certificate installed and auto-renewal configured.
- [ ] HSTS decision reviewed; enable `HSTS_ENABLED=true` only after HTTPS is stable.

## Storage / Permissions

- [x] Storage directories exist locally.
- [ ] `storage/logs`, `storage/cache`, `storage/sessions`, `storage/private`, and public upload dirs writable by PHP user.
- [ ] Private KYC storage not web-accessible.
- [x] Sensitive runtime session artifacts removed from release package source and guarded by release check.

## Email

- [ ] Configure SMTP (`MAIL_*`) or approved transactional provider.
- [ ] Send password reset test.
- [ ] Send email verification test.
- [ ] Verify sender/domain SPF, DKIM, DMARC.

## Payments

- [!] Live payments are externally unverified.
- [ ] Disable mock payments in production unless intentionally running demo mode.
- [ ] Configure provider credentials and production endpoints.
- [ ] Verify webhook signatures and idempotency.
- [ ] Test success, failure, cancellation, duplicate callback, and refund flows.

## External APIs

- [ ] OAuth Google/Apple credentials verified if enabled.
- [ ] SMS provider verified if enabled.
- [ ] WhatsApp Business API verified if enabled.
- [ ] Maps provider/CSP verified if enabled.
- [ ] Analytics configured and privacy-reviewed if enabled.

## Queues / Cron

- [ ] Schedule `php cli/stayin.php bookings:expire-holds`.
- [ ] Schedule/worker for `php cli/stayin.php notifications:send` or `jobs:run`.
- [ ] Schedule finance reconciliation if operationally required.
- [ ] Monitor failed/slow jobs.

## Caching

- [ ] Confirm cache directory writable.
- [ ] Clear stale cache during deployment.
- [ ] Verify service worker cache version after deploy.

## Logging / Monitoring

- [ ] Set production `LOG_LEVEL` appropriately.
- [ ] Confirm logs do not expose secrets.
- [ ] Configure uptime checks for `/health`.
- [ ] Configure error monitoring/alerting.
- [ ] Configure disk/log rotation.

## Backups

- [ ] Automated database backups configured.
- [ ] Backup encryption enabled.
- [ ] Restore procedure tested.
- [x] Existing local backups with user data quarantined outside repository; owner should decide long-term retention/destruction.

## Rollback

- [ ] Previous release artifact retained.
- [ ] DB rollback/forward-fix plan documented for each migration.
- [ ] Maintenance page/process ready.

## Smoke Tests

- [x] Local public smoke: `/health`, `/api/v1/ping`, `/login`, `/forgot-password`, static PWA files.
- [ ] Production smoke after deploy.
- [x] Authenticated guest smoke via `composer test`.
- [x] Authenticated host smoke via `composer test`.
- [x] Authenticated admin smoke/RBAC denial smoke via `composer test`.
- [ ] Booking/checkout smoke with approved payment mode.