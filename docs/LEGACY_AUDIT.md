# Repository audit

Initial checkout: framework-free PHP/PDO scaffold, CSS tokens and approved logo/icons. No prior React/Prisma/PostgreSQL runtime is present. No AGENTS.md or README was found. User changes were already present and have been preserved.

KEEP: existing database, IDs, images, branding, CSS tokens, PDO abstraction, environment configuration, routing and security primitives where correct.
REPAIR: controller resolution, view response contract, route constraints, configuration overrides, CSRF on API writes, auth cache, middleware parameters and missing imports.
IMPLEMENT: templates, browser enhancements, guest/host/admin controllers, booking and finance services, recovery email, migrations, tests and operational CLI.
MIGRATE: additive tables only; no legacy finance backfill without reconciliation.
ARCHIVE / REMOVE LATER: nothing removed. Existing schema dump includes DROP statements and is reference only; new baseline is non-destructive.

Database: MySQL 8.0.40, 32 existing tables. Private backup and structure baseline recorded before migrations. Legacy availability semantics are not documented: the new engine uses explicit date inventory supplied by hosts and deducts overlapping bookings/holds; missing dates are unavailable. Existing financial records will not be silently converted into a new ledger.
