# Remaining Issues

## Critical

- None currently known in the deployable working tree. Sensitive database/session/local backup artifacts were quarantined outside the repository and `composer release:check` now blocks release packaging if they reappear.

## High

- **External integrations require operator verification:** live payments/webhooks, SMTP, SMS, WhatsApp Business API, OAuth, maps, and analytics need real credentials/provider sandbox or production verification before those features are enabled.
- **Authenticated workflows need full browser E2E:** guest/host/admin dashboard access and RBAC denial smoke now pass in `composer test`; deeper booking/payment/KYC/moderation workflows still need browser/API workflow tests with role-specific accounts.
- **Extensive pre-existing uncommitted changes:** repository owner must review/accept changes before release.
- **Full all-file PHP lint timed out in this tool environment:** run in CI with a longer timeout.

## Medium

- Dashboard search inputs and date-range pills appear visually present but not wired to filtering/search behavior.
- Full accessibility audit toward WCAG 2.2 AA remains pending.
- Full responsive testing at 320/375/390/414/768/1024/1280/1440/1920 px remains pending.
- API validation/authorization matrix testing remains incomplete.
- Production security headers/cookie settings must be verified behind real HTTPS/proxy.
- Service worker behavior should be browser-tested, including offline fallback and subdirectory deployments.

## Low

- Documentation `docs/00-PREFLIGHT-AUDIT.md` contains stale historical findings that may now be fixed and should be reconciled with this report.
- Some backup `.bak` files and old view snapshots remain in the tree; review whether to keep outside release artifacts.
- Large pages (`/search`, `/stays`) should receive performance profiling and image optimization review.

## External Verification

- Payment success/failure/cancellation/refund/webhook duplicate callback.
- SMTP delivery and bounce behavior.
- OAuth login/account-linking behavior.
- SMS and WhatsApp message delivery.
- Maps tile loading/CSP changes.
- Analytics consent/privacy behavior.

## Enhancements

- Add richer unit/integration tests around money calculations, booking concurrency/idempotency, and RBAC.
- Add Playwright/Cypress smoke flows for guest/host/admin roles.
- Add CI pipeline for composer validate, platform requirements, PHP lint, tests, and HTTP smoke.