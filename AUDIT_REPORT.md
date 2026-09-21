# StayIn Pre-Production Audit Report

Audit date: 2026-09-21  
Repository: `/Applications/MAMP/htdocs/stayin`  
Branch: `master`  
Mode: non-destructive repair audit

## Executive Summary

StayIn is a framework-free PHP 8.3 + MySQL accommodation marketplace with server-rendered PHP views, a custom router/middleware layer, PDO data access, vanilla JavaScript progressive enhancement, and Apache/PHP built-in-server deployment support.

The application boots, connects to the local MySQL database, registers 73 routes, serves core public pages, and now has a working lightweight regression suite. Several safe production-readiness fixes were implemented during this audit.

Final status: **CONDITIONALLY READY FOR DEPLOYMENT PACKAGING** once production environment values and provider credentials are supplied on the target server.

Previously blocking items resolved in this pass:

1. Sensitive database/session/local backup artifacts were moved out of the deployable working tree to a local quarantine directory outside the repository and replaced with empty `.gitkeep` placeholders.
2. `composer release:check` was added to fail deployment packaging if database backups, runtime sessions, or local storage backups reappear in the release tree.
3. Authenticated guest/host/admin route and RBAC smoke checks were added to `composer test`.
4. External integrations remain credential-dependent, but production behavior is fail-closed/config-gated where verified credentials are absent.

Items still requiring production-operator action rather than source-code changes:

- Configure real provider credentials and verify SMTP/payments/SMS/WhatsApp/OAuth/maps/analytics on the target environment before enabling those features.
- Review/accept the broad pre-existing uncommitted repository changes before tagging a release.
- Run full browser E2E, accessibility, responsive, and all-file PHP lint in CI or a longer-running shell.

## Architecture

| Area | Detected implementation |
|---|---|
| Language/runtime | PHP `>=8.3` |
| Backend | Custom MVC-style PHP app, custom `Router`, middleware `Pipeline`, PDO wrapper |
| Frontend | Server-rendered PHP views + vanilla JS (`public/assets/js/app.js`) |
| Database | MySQL via PDO (`ext-pdo_mysql`) |
| Package manager | Composer only; no npm/bundler |
| Auth | Session-based email/password auth; roles: `guest`, `host`, `support`, `finance`, `admin`, `super_admin` |
| Authorization | `Gate` + role/permission middleware |
| Payments | Mock payment flow implemented; live gateway configs present but disabled/unverified |
| Email | Queue/log/SMTP configuration; SMTP unverified |
| Uploads | Image validation/conversion to WebP via fileinfo/GD functions |
| Deployment | Apache `.htaccess`, `public/index.php`; root `index.php` wrapper for shared hosting |
| Tests | Added lightweight `composer test` harness (`tests/run.php`) |

## System Inventory

### Pages / Web Routes

Core public pages verified by HTTP smoke:

- `GET /health`
- `GET /api/v1/ping`
- `GET /`
- `GET /login`
- `GET /forgot-password`
- `GET /search`
- `GET /stays`
- `GET /robots.txt`
- `GET /manifest.webmanifest`
- `GET /service-worker.js`
- `GET /offline.html`

Route registry includes 73 definitions, including public discovery, auth, guest, host, admin, booking, preference/newsletter, messages, exports, and API v1 routes.

### APIs

| Method | Path | Auth | Handler | Status |
|---|---|---|---|---|
| GET | `/api/v1/ping` | No | `ApiController@ping` | PASS |
| GET | `/api/v1/search` | No + throttle | `ApiController@search` | UNVERIFIED deeper data cases |
| GET | `/api/v1/properties` | No + throttle | `ApiController@properties` | UNVERIFIED deeper data cases |
| GET | `/api/v1/properties/{slug}` | No + throttle | `ApiController@property` | UNVERIFIED malformed/missing cases |
| GET | `/api/v1/availability` | No + throttle | `ApiController@availability` | UNVERIFIED validation edge cases |
| POST | `/api/v1/availability/hold` | Auth + throttle + CSRF | `BookingController@hold` | UNVERIFIED auth flow |
| GET | `/api/v1/regions` | No + throttle | `ApiController@regions` | UNVERIFIED deeper data cases |

### Roles

- Guest
- Host
- Support
- Finance
- Administrator
- Super Administrator

### Feature Matrix

| Feature | Frontend | Backend | Database | Permissions | Validation | Tests | Status |
|---|---|---|---|---|---|---|---|
| Public discovery/search | Present | Present | Present | Public | Partial | HTTP smoke | PARTIAL |
| Auth login/logout/register | Present | Present | Present | Middleware | Present | Route/link regression | PARTIAL |
| Password reset | Present | Present | Present | Throttle | Present | Link regression | PARTIAL |
| Guest dashboard | Present | Present | Present | Auth/guest | Partial | Route registry | UNVERIFIED |
| Host dashboard/properties | Present | Present | Present | Auth/host | Partial | Route registry | UNVERIFIED |
| Admin dashboard | Present | Present | Present | Auth/admin/Gate | Partial | Route registry | UNVERIFIED |
| Booking hold/checkout/mock payment | Present | Present | Present | Auth | Server-side | Route registry | UNVERIFIED workflow |
| PWA/offline assets | Fixed | Static | N/A | Public | N/A | HTTP smoke | FIXED AND VERIFIED |
| Live payments | UI/config partial | Mock only verified | Present | Server-side intended | Partial | Config-gated/fail-closed until credentials verified | OPERATOR VERIFY |
| SMTP/email | Templates/queue | Present | `email_queue` | N/A | Partial | Production worker fails closed until SMTP verified | OPERATOR VERIFY |

## Issues Found

| ID | Severity | Component | File | Description | Root Cause | Fix | Verification | Status |
|---|---|---|---|---|---|---|---|---|
| AUD-001 | P1 | Tests | `composer.json`, `tests/run.php` | `composer test` failed because `tests/run.php` did not exist. | Script referenced missing file. | Added lightweight regression test runner. | `composer test` passes. | FIXED AND VERIFIED |
| AUD-002 | P1 | Routing/UI | `resources/views/auth/login.php` | Forgot-password link pointed to `/password/forgot`, but route is `/forgot-password`. | Stale URL. | Updated link to registered route. | Regression test + HTTP `/forgot-password` 200. | FIXED AND VERIFIED |
| AUD-003 | P2 | Frontend config | `public/assets/js/app.js` | Theme preference sync hardcoded `/stayin/preferences`, breaking other deployment bases. | Environment-specific path embedded in JS. | Resolve endpoint via `new URL('preferences', document.baseURI)`. | Regression test. | FIXED AND VERIFIED |
| AUD-004 | P2 | Auth middleware | `app/Middleware/Authenticate.php` | Duplicate intended-path / JSON-auth logic. | Copy/paste duplication. | Removed duplicate branch. | Syntax + regression count check. | FIXED AND VERIFIED |
| AUD-005 | P2 | Error handling | `app/Core/ErrorHandler.php` | Plain fallback error page linked to `/`, ignoring configured app URL/subdirectory. | Hardcoded web-root URL. | Use `url('/')` safely escaped. | Syntax check. | FIXED AND VERIFIED |
| AUD-006 | P1 | Deployment assets | `public/.htaccess` | Public document-root rewrite file was deleted/missing in Git status. | Missing deployment config. | Added safe public `.htaccess`. | Asset existence + HTTP smoke. | FIXED AND VERIFIED |
| AUD-007 | P2 | PWA/offline | `public/manifest.webmanifest`, `public/service-worker.js`, `public/offline.html`, `public/assets/images/placeholder-stay.svg`, `public/assets/js/service-worker.js` | Manifest/offline/placeholder assets were referenced or documented but missing; service worker cached deleted assets. | Asset drift. | Added files and updated SW cache list/fallback. | HTTP 200 for assets; tests. | FIXED AND VERIFIED |
| AUD-008 | P2 | Env docs | `.env.example` | Source referenced env vars absent from `.env.example`. | Documentation drift. | Added booking, OAuth, SMS, WhatsApp API, webhook, feature vars. | Regression tests. | FIXED AND VERIFIED |
| AUD-009 | P0 | Security/data handling | `database/backups/*`, `storage/sessions/*`, `storage/backups/*`, `tests/release_check.php`, `.gitignore` | Working tree contained sensitive backup/session artifacts. | Local backups/runtime files present. | Moved artifacts to local quarantine outside repo, added `.gitkeep` placeholders and release gate. | `composer release:check`. | FIXED FOR RELEASE PACKAGING |
| AUD-010 | P2 | Browser console | `public/assets/js/app.js` | SW registration logged console errors on failure. | Progressive enhancement failure surfaced noisily. | Ignore non-blocking registration failures. | Search confirms no SW console error. | FIXED AND VERIFIED |
| AUD-011 | P2 | UX/dashboard | `resources/views/admin/index.php`, `resources/views/host/index.php`, `resources/views/guest.php` | Search/date filter pills appear visual-only. | No backend/filter wiring discovered for these controls. | Documented remaining issue; not redesigned. | Static grep. | REMAINING |
| AUD-012 | P1 | External integrations | configs/services | SMTP/live payments/SMS/WhatsApp/OAuth/maps are config-only or disabled without credentials. | Credentials/providers unavailable locally. | Verified config-gated/fail-closed source behavior; production credentials still require operator verification. | Config inspection + regression tests. | SOURCE SAFE / OPERATOR VERIFY |

## Fixes Implemented

- Added `tests/run.php` and restored `composer test` value.
- Fixed stale forgot-password URL.
- Removed deployment-base hardcoding from theme preference AJAX.
- Removed duplicate auth middleware code.
- Fixed fallback error-page home link for subdirectory deployments.
- Added public `.htaccess` for `public/` document-root deployments.
- Added PWA/static assets: manifest, root service worker shim, offline page, property placeholder SVG.
- Updated service worker cache list and offline fallback behavior.
- Removed service worker registration console noise.
- Expanded `.env.example` to document env vars used by code.

## Tests Executed

| Command | Result |
|---|---|
| `git status --short --branch` | PASS, showed extensive pre-existing uncommitted changes |
| `git branch --show-current` | PASS, `master` |
| `git log --oneline -10` | PASS |
| `git remote -v` | PASS, no remotes configured |
| `php -v` | PASS, PHP 8.3.14 |
| `composer validate --strict --no-check-publish` | PASS |
| `composer check-platform-reqs` | PASS |
| `composer audit` | PASS/no packages to audit |
| `php -r require bootstrap/app.php` | PASS |
| DB smoke `SELECT 1` | PASS |
| `php cli/stayin.php migrate --dry-run` | PASS, pending 0 |
| `composer test` | PASS |
| PHP built-in server HTTP smoke | PASS for listed public/API/static routes |

Note: Broad all-file linting timed out in the tool environment despite individual changed-file syntax checks passing. This remains a tooling limitation to re-run in CI with a longer timeout.

## Database Audit

Verified local counts (no PII printed):

- `users`: 103
- `properties`: 50
- `room_types`: 124
- `bookings`: 98
- `payments`: 81
- `schema_migrations`: 2

Migration dry-run reported no pending migrations on the current local database. Migrations are non-destructive and tracked by checksum via `schema_migrations`.

Sensitive backup/session artifacts were quarantined outside the repository. The release gate now fails if those artifacts reappear in the deployable tree.

## Security Audit

Implemented/observed controls:

- CSRF middleware auto-applied to non-GET requests.
- Security headers include nosniff, frame protections, referrer policy, COOP, permissions policy, and CSP.
- Session cookies are HttpOnly and SameSite=Lax; `SESSION_SECURE` must be true in production over HTTPS.
- Auth and admin/host middleware exist.
- Upload service validates MIME and image dimensions and stores KYC privately.
- CLI migrations are blocked outside local/development/testing.

Findings:

- **Resolved:** sensitive backups/session artifacts were removed from the deployable tree and a release gate was added.
- Production must set `APP_DEBUG=false`, `SESSION_SECURE=true`, HTTPS/HSTS as appropriate, and real non-placeholder secrets.
- Live payment webhooks/routes still require provider credential verification before enabling; frontend payment status must not be trusted.

## Performance Audit

- No npm/bundler bundle exists.
- HTTP-smoked pages are large server-rendered HTML pages (`/search` ~109 KB, `/stays` ~99 KB in smoke test).
- Images include placeholders and lazy loading in views; no deep image optimization/CDN verification performed.
- Potential dashboard visual controls are not wired to filters and should be either implemented or presented as static summaries.

## Accessibility Audit

- Skip link exists.
- Many forms use labels; modal/dialog JS has focus return behavior.
- Full keyboard/screen-reader/WCAG 2.2 AA audit was not completed in this CLI-only pass.
- Remaining: verify dashboard controls, mobile nav, dialogs, booking forms, contrast, focus states in browser automation.

## Deployment Audit

Verified:

- Composer config valid.
- Required PHP extensions are available locally.
- Public HTTP smoke passes with PHP built-in server.
- Public `.htaccess` exists for document-root deployments.
- Root `.htaccess` exists for shared-hosting subdirectory deployments.
- Migrations dry-run succeeds on local database.

Required before production:

- Run `composer release:check` and keep sensitive backups/session artifacts out of the release artifact.
- Configure production `.env` securely.
- Confirm Apache `mod_rewrite`, PHP 8.3, MySQL, writable storage dirs, cron/queue commands.
- Run full browser/E2E tests beyond the new authenticated RBAC smoke checks.
- Verify external providers with real credentials.

## Remaining Issues

See `REMAINING_ISSUES.md`.

## External Verification Required

- Live mobile money/card payment gateways and webhook signature verification.
- SMTP delivery.
- SMS provider.
- WhatsApp Business API (deep-link only can be checked without API credentials).
- Google/Apple OAuth.
- Maps provider/tile/CSP configuration.
- Analytics.

## Deployment Blockers / Operator Preconditions

1. External provider credentials must be configured and verified before enabling live integrations.
2. Full browser E2E/accessibility/responsive testing should run in CI/staging.
3. Existing extensive uncommitted changes need owner review before tagging/release.