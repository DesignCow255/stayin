# StayIn — §0 Pre-flight Audit

**Date:** 2026-09-20
**Repo:** `/Applications/MAMP/htdocs/stayin` · branch `master` · HEAD `31bd32e`
**Scope:** Full application audit prior to the premium redesign programme.

---

## 0.1 Executive summary

StayIn is a **framework-free PHP 8.3 + MySQL 8 server-rendered marketplace with a vanilla-JS progressive-enhancement layer** — not a React/Next.js application. There is **no build step, no npm, no bundler, no CSS framework**. Every screen is a plain-PHP view rendered by a bespoke `View` class, styled by 7 hand-written CSS files using CSS custom properties, and enhanced by a single 6.9 KB `app.js`.

The **backend is genuinely strong**: real transactional booking maths in integer minor units, idempotent payment events, availability blocks per room per date, KYC workflow, audit logging, CSRF, throttling, CSP, 8 real property types, 42 published properties, 124 room types, 199 photographs, 96 bookings.

The **presentation layer is the weak link**: the visual language is a pre-redesign "limestone" editorial theme (brown `#7A5C35`), the mobile navigation is a collapsed desktop menu, there is no saved/favourite UI, no gallery lightbox wiring, no map, no structured data, no analytics, no skeleton/empty/error state system, and roughly **a dozen view↔controller data contracts and internal links are silently broken** (details in §0.18).

**Strategic verdict:** the redesign must be applied as a **layered presentational system on top of the existing PHP architecture** (per §43). No backend field, route, data relationship or business rule is renamed or removed. Broken contracts are *repaired to the backend's real contract*, never papered over with mock data.

---

## 1. Framework, versions, package manager, build tool, monorepo layout

| Item | Finding |
|---|---|
| Language | PHP **8.3+** (declared in `composer.json` require); runtime under MAMP |
| Application type | Framework-free, hand-rolled MVC-ish. ~50 core classes in `app/Core` |
| Database | MySQL **8.0.40** (MAMP), PDO MySQL, `utf8mb4_unicode_ci`, InnoDB, FKs enforced |
| Package manager | **Composer**, effectively unused — `composer.lock` is 763 bytes and ships only the generated autoloader. **Zero runtime third-party PHP libraries** |
| Build tool | **None.** No webpack/vite/esbuild/rollup, no npm, no `package.json`. Assets are served directly from `public/assets` and cache-busted by `filemtime()` in the `asset()` helper |
| Monorepo layout | Single app, no workspace packages |
| Autoload | PSR-4 `App\` → `app/`, plus `files: app/Support/helpers.php` |
| Front controller | `public/index.php`; root `index.php` re-requires it for shared hosting |
| Web server | Apache + `mod_rewrite` via root `.htaccess`, `RewriteBase /stayin/`; blocks `app/ config/ database/ resources/ routes/ storage/ vendor/ tests/ cli/ docs/` and dotfiles |
| Local run | Served at `http://localhost/stayin/` (verified HTTP 200, ~0.13 s TTFB) |
| Composer scripts | `serve` (`php -S 127.0.0.1:8888 -t public`), `migrate`, `seed`, `test` (`php tests/run.php`) |

### Directory map

```
app/
  Core/         App, Router, Route, RouteDefinition, Pipeline, Request, Response, View,
                Controller, Database, Config, Env, Session, Cache, Csrf, Security,
                Validator, Migrator, Logger, ErrorHandler, HttpException,
                RouteNotFoundException, MethodNotAllowedException, ValidationException,
                BusinessException, Middleware, MiddlewareInterface, Container,
                SqlScript, Autoloader, RequestContext, DummyAuthService
  Controllers/  Admin, Api, Auth, Booking, Export, Guest, Health, Home, Message,
                Page, Preference, Property, Search, Seo, DatabaseSitemap  (15)
  Middleware/   Authenticate, RedirectIfAuthenticated, EnsureRole, EnsurePermission,
                EnsureHost, EnsureAdminAccess, EnsureEmailVerified, VerifyCsrfToken,
                ThrottleRequests, SetLocale, ShareViewData, SecurityHeaders,
                SessionTarget, TrackLastActivity, CheckMaintenanceMode  (15)
  Models/       Property, User, HeroSlide  (3)
  Repositories/ SearchRepository  (1)
  Services/     AuthService, AccountService, BookingService, PaymentService, HostService,
                UploadService, FinanceService, NotificationService, MailService,
                RateLimiter, Gate, Money  (12)
  Payments/     PaymentGatewayInterface, MockGateway
  Support/      helpers.php
bootstrap/      app bootstrap
config/         15 config files (app, auth, booking, currency, database, display, features,
                integrations, locale, mail, middleware, payments, pricing, security, seo)
database/       migrations/ (1 file), schema/ (2 reference dumps), backups/ (2 snapshots)
resources/
  views/        39 PHP templates across 11 folders
  lang/         en/ui.php, sw/ui.php
routes/         web.php (~50 named routes), api.php (7 routes)
public/         index.php, .htaccess, manifest.webmanifest, service-worker.js, offline.html,
                assets/{css,js,fonts,images,icons,uploads,vendor}
storage/        logs, sessions, private/kyc, private, cache
tests/          run.php
cli/            stayin.php
docs/           LEGACY_AUDIT.md, DATABASE_BASELINE.md, + this audit
```

---

## 2. Routing model, route inventory, dynamic segments

**Model:** config-file route definitions (not file-based). A bespoke `App\Core\Router` supports `get`/`post`, named routes (`Router::url('name')`), `->where([...])` regex constraints on dynamic segments, `->middleware([...])` per route, and `->group(['prefix'=>..., 'middleware'=>...])`.

### Public web routes (`routes/web.php`) — 50 named routes

**Health / SEO**
`GET /health` · `GET /robots.txt` · `GET /sitemap.xml` · `GET /sitemap-properties.xml` · `GET /sitemap-destinations.xml` · `GET /sitemap-pages.xml`

**Discovery**
`GET /` (home) · `GET /search` · `GET /stays` · `GET /stays/{region}` `[a-z0-9-]+` · `GET /property/{slug}` `[a-z0-9-]+`

**Static / legal**
`GET /about` · `/contact` · `/privacy` · `/terms` · `/help`

**Auth**
`GET|POST /login` · `GET|POST /register` · `POST /logout` · `GET|POST /forgot-password` · `GET /reset-password/{token}` `[a-f0-9]{64}` · `POST /reset-password` · `GET /verify-email` · `POST /verify-email/resend` · `GET /verify-email/{token}`

**Guest area** — group `{prefix:/guest, middleware:[auth]}`
`GET /guest` · `/guest/bookings` · `/guest/favourites` · `/guest/profile` · `POST /guest/profile`

**Host portal** — group `{prefix:/host, middleware:[auth,host]}`
`GET /host` · `/host/properties` · `/host/properties/create` · `POST /host/properties` · `GET /host/properties/{id}/edit` `[0-9]+` · `POST /host/properties/{id}` · `GET /host/bookings`

**Admin control centre** — group `{prefix:/admin, middleware:[auth,admin]}`
`GET /admin`

**Booking / guest actions (session-aware)**
`GET /checkout/{reference}` `[A-Z0-9-]{4,30}` · `POST /checkout/{reference}/pay` · `POST /bookings/hold` · `POST /bookings/{reference}/cancel` · `GET /bookings/{reference}/receipt` · `POST /guest/favourites` · `POST /guest/notifications/read` · `POST /guest/reviews` · `POST /guest/searches` · `GET /guest/export` · `POST /guest/privacy` · `GET|POST /messages/{id}` · `POST /host/properties/{id}/actions` · `POST /host/kyc` · `POST /host/bookings/complete` · `POST /admin/actions` · `GET /admin/kyc/{id}/document` · `GET|POST /admin/content` · `GET /exports/bookings` · `POST /preferences` · `POST /newsletter` · `GET|POST /newsletter/{action}/{token}`

### JSON API (`routes/api.php`) — prefix `/api/v1`
`GET /ping` · `GET /search` · `GET /properties` · `GET /properties/{slug}` · `GET /availability` · `POST /availability/hold` · `GET /regions`

**Throttles in use:** `login`, `register`, `password_reset`, `api`, `search`, `payment_init`, `hold_create` (defined in `config/middleware.php` + `Services\RateLimiter`, backed by the `rate_limits` table).

### Dynamic segments
`{region}` `[a-z0-9-]+` · `{slug}` `[a-z0-9-]+` · `{id}` `[0-9]+` · `{token}` `[a-f0-9]{64}` · `{reference}` `[A-Z0-9-]{4,30}` · `{action}` (newsletter: `confirm|unsubscribe`)

### URL patterns in production
`/` · `/search?region=…&q=…` · `/stays` · `/stays/dar-es-salaam` (slugified region) · `/property/test-ocean-pearl-hotel-20-fdfd4d` · `/guest/…` · `/host/…` · `/admin/…`

**Note:** `/buy/...`, `/rent/...`, `/properties/...` and `/developments/...` from the brief **do not exist and are not supported by the data model** (see §0.5 Capability Map).


---

## 3. Component inventory and reusable primitives

Views are plain PHP. Three composition mechanisms exist in `App\Core\View`:

| Mechanism | Signature | Location | Used for |
|---|---|---|---|
| Layout inheritance | `View::start('content')` / `View::stop()` + `layouts/app.php` | `resources/views/layouts` | every page shell |
| Component | `View::component('name', $data)` → `components/name.php` | `resources/views/components` | `property-card`, `pagination` |
| Partial | `View::partial('name', $data)` → `name.php` | `resources/views/partials` | `booking-card`, `flash-status`, `seo-head` |

### Existing reusable primitives (real, in use)
`layouts/app.php` (header + nav + footer shell) · `partials/seo-head` · `partials/flash-status` · `partials/booking-card` · `components/property-card` · `components/pagination` · `errors/{403,404,429,500,503}` · `emails/layout` · `emails/verify-code`

### CSS primitives (class-level, `components.css` + `pages.css`)
`.btn` + `--primary/--ghost/--quiet/--danger/--sm/--block` · `.badge` + `--success/--warning/--danger/--info/--brand` · `.alert` + 4 variants · `.toast-region` · `.kpi` · `.card` · `table.data` · `.divider` · `.pill-list` · `.gallery-grid` · `.empty-state` · `.property-card*` · `.search-bar` · `.hero*` · `.destination-card*` · `.eyebrow` · `.text-link` · `.breadcrumb` · `.filter-panel` · `.more-filters` · `.room-card*` · `.quote*` · `.gallery*` · `.property-*` · `.amenities-list` · `.booking-card` · `.photo-dialog` · `.contact-*`

### JS primitives (all inside one file, `public/assets/js/app.js`)
`Theme` (light/dark/system, localStorage) · `initNavToggle` · `initAnnouncer` (aria-live) · `initDateFields` · `initGuestCounter` · `initFlashDismiss` · `initFormValidation` · `initImageLazyLoad` · `initServiceWorker`. Exposes `window.StayIn = { Theme }`.

### Empty scaffolding (declared but unused — a build target, not a dependency)
`public/assets/js/core/`, `js/components/`, `js/pages/`, `js/services/`, `js/utils/` all exist but contain **zero files**. The component/JS module layer must be created.

### Undefined classes referenced by views (styling gaps)
`.page-section` · `.step` · `.step__num` · `.pagination` · `.stats-grid` · `.host-nav__link` · `.guest-nav__link` · `.badge--sm` · `.badge--pending` · `.btn--lg` · `.form-group` · `.form-row` · `.input` · `.auth-form` · `.auth__title` · `.checkbox-label` · `.admin-stats` · `.checkout-layout` · `.payment-row` · `.property-form`. Views also use `<table class="table">` while CSS styles `table.data`, and `guest.php` contains **Tailwind utility classes that do not exist in this project** (`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap:var(--space-3)`).

---

## 4. Styling stack

- **CSS custom properties only.** No Tailwind, no CSS-in-JS, no preprocessor, no PostCSS.
- 7 stylesheets, loaded in this order by `partials/seo-head.php`: `tokens.css` → `base.css` → `layout.css` → `components.css` → `pages.css` → `utilities.css` → `responsive.css` (≈ 58 KB raw, cache-busted by mtime).
- **Token locations:** `tokens.css` defines palette, type scale, spacing, radii, 3 shadows, 3 transitions, z-index scale, container widths, `--focus-ring`.
- **Two themes:** `:root` (limestone/warm brown) and `[data-theme="dark"]`, plus `[data-theme="system"]` resolved by JS + `prefers-color-scheme`.
- **Current brand:** `--brand: #7A5C35` (warm brown), `--action-bg: #29382E` (deep green), display serif **Fraunces**, interface **Source Sans 3**.
- **Breakpoint vocabulary:** 320 / 360 / 390 / 430 / 720 / 860 / 1100 / 1440 (CSS only — JS uses no media queries beyond `prefers-color-scheme`).
- `prefers-reduced-motion` zeroes all three transition tokens.
- **Fonts are self-hosted subset TTFs** (`font-0.ttf` … `font-5.ttf`) with `font-display: swap` — CSP-safe (`font-src 'self' data:`) and offline-capable; no Google Fonts dependency.
- **Icons:** Font Awesome 6 Free, **vendored locally** at `public/assets/vendor/fontawesome/`, loaded as a separate render-blocking `<link>` in `layouts/app.php`.

---

## 5. State management

- **Server state only.** PHP sessions (`App\Core\Session`, file driver at `storage/sessions`), plus DB-backed `sessions` and `rate_limits` tables.
- **No client state library.** Redux/Zustand/Jotai/Context/React Query/SWR are N/A for this stack.
- Client-side ephemeral state: `localStorage['stayin_theme']`, DOM classes (`nav.is-open`), `<details>` open state.
- Form input survives failed validation via **flash keys** read through the `old()` helper.
- **Implication for the redesign:** the URL is the state container for search/filters (already true — `SearchController` reads `$r->query()`), satisfying "persist filters in URL / back-button safe" without introducing a client framework. All new interactivity must remain progressive enhancement.


---

## 6. Data layer

- **Primary:** server-rendered PHP + PDO (`App\Core\Database` — `select`, `first`, `scalar`, `insert`, `update`, `execute`, `transaction`, `lockFirst`). Parameter binding throughout; `SearchRepository` validates and whitelists every filter key.
- **Secondary:** JSON REST API v1 (`/api/v1/*`) returning `{ok, data}` — **no client consumer yet**.
- **No GraphQL, no tRPC, no WebSocket, no SSE, no server actions.**
- **Caching:** `App\Core\Cache` (file-based) + `storage/cache`; sitemap caching via `seo.sitemap.cache_hours`.
- **Search engine:** `SearchRepository::search()` — whitelist `q, region, property_type, rating, verified, currency, min_price, max_price, rooms, guests, check_in, check_out, sort`; `EXISTS` subquery over `room_types`; live-availability subquery over `availability_blocks` + `bookings`; `match()` sorting; 24/page.
- **Data reality (live `stayin_db`):** 50 properties (42 `published`, 8 `pending`) · 124 `room_types` (all `active`, all **TZS**) · 199 `property_images` · 96 `bookings` (55 completed, 18 expired, 9 confirmed, 8 disputed, 6 cancelled) · 103 users · 20 regions with published inventory · 8 active `hero_slides` · active room prices **TZS 15,000 – 375,000 / night**, mean ≈ TZS 133,177.
- **Real 8 property types:** `hotel`, `lodge`, `guest_house`, `homestay`, `chumba_kimoja`, `villa`, `apartment`, `serviced_apartment`.

---

## 7. Authentication

| Aspect | Implementation |
|---|---|
| Mechanism | Session cookie (`stayin_session`, `SESSION_LIFETIME=120` min), `SameSite`, optional `Secure` |
| Service | `App\Services\AuthService` — `attempt`, `login`, `logout`, `user`, `id`, `check`, `roles`, `hash` |
| Providers | Email + password only. Google/Apple slots in `config/auth.php` + `integrations.php`; **both disabled** |
| Roles | `users.role` — `guest`, `host`, `admin`, `super_admin` |
| Permissions | `App\Services\Gate` + `EnsurePermission`; matrix in `config/auth.php` (`properties.moderate`, `properties.view`, `kyc.review`, `payments.view`, `finance.view`, `finance.manage`, `settlements.approve`, `users.manage`, `users.view`, `cms.manage`, `audit.view`, `admin.access`) |
| Guards | `Authenticate`, `RedirectIfAuthenticated`, `EnsureRole`, `EnsurePermission`, `EnsureHost`, `EnsureAdminAccess`, `EnsureEmailVerified`, `CheckMaintenanceMode` |
| CSRF | `VerifyCsrfToken` on all POST; `csrf_field()`; `Csrf::token()` shared to all views |
| Throttling | Per-action buckets (login, register, password_reset) via `ThrottleRequests` + `rate_limits` |
| Recovery | `AccountService::resetLink` (SHA-256 token, 1 h TTL); `verification` (48 h TTL, `email_verifications`) |
| Lockout | `users.failed_logins`, `users.locked_until` |
| KYC | `host_kyc` — `document_path`, `status(pending/verified/rejected/suspended)`, `reviewer_id`, `review_note`, `submitted_at`, `reviewed_at`. Documents live **outside the web root** in `storage/private/kyc` and stream only to `kyc.review` holders via `GET /admin/kyc/{id}/document` with `no-store` |
| **Not present** | Passkeys, magic links, OTP, MFA — do not exist. Brief §18 items beyond password+email are **new capability requiring backend work** |

---

## 8. Property data model

### Core tables (structure verified against `DESCRIBE`)

**`properties`**
`id` · `uuid` unique · `host_id` → `users.id` · `property_type` **ENUM(hotel, lodge, guest_house, homestay, chumba_kimoja, villa, apartment, serviced_apartment)** · `name` · `slug` unique · `description_en` · `description_sw` · `address` · `region` (indexed) · `district` · `ward` · `latitude` dec(10,7) · `longitude` dec(10,7) · `landmark` · `check_in_time` (14:00) · `check_out_time` (11:00) · `status` **ENUM(draft, pending, published, suspended)** · `verification_status` **ENUM(pending, verified, rejected)** · `featured` · `subscription_tier` ENUM(standard, professional, lodge, hotel, enterprise) · `subscription_status` ENUM(trial, active, expired, cancelled) · `subscription_expires_at` · `rating` dec(3,2) · `review_count` · `amenities` **JSON** · `house_rules` · `cancellation_policy` · timestamps

**`room_types`** — the actual unit of sale (nightly)
`id` · `property_id` · `name` · `description` · `max_guests` · `bed_configuration` · `base_price` dec(14,2) · `currency` **ENUM(TZS, USD)** · `quantity` · `status` ENUM(active, inactive) · timestamps

**`property_images`**
`id` · `property_id` · `file_url` · `thumbnail_url` · `caption` · `sort_order` · `is_primary` · `created_at`

**`availability_blocks`** — per room_type, per date
`id` · `room_type_id` · `date` · `available_quantity` · `price_override` · `minimum_stay` · `status` ENUM(available, blocked, maintenance, closed) · **UNIQUE(room_type_id, date)** — 22,574 rows

**`bookings`** — `booking_reference` UNIQUE · `guest_id` · `property_id` · `room_type_id` · `check_in` · `check_out` · `guests` · `rooms` · `subtotal` · `discount_amount` · `deposit_amount` · `total_amount` · `currency` · `status` ENUM(pending, awaiting_payment, confirmed, completed, cancelled, expired, disputed) · `payment_status` · `expires_at` (hold) · `commission_*`
**Companions:** `booking_price_snapshots` (immutable quote audit), `booking_status_history`, `reviews`, `favourites`, `saved_searches`

### Relations
`users 1—N properties` · `properties 1—N room_types` · `properties 1—N property_images` · `room_types 1—N availability_blocks` · `properties 1—N bookings` · `bookings 1—1 booking_price_snapshots` · `properties 1—N reviews` · `users N—N properties` via `favourites`

### Gaps that constrain the redesign (must not be faked)
No `bedrooms` / `bathrooms` / `floor_area` / `land_size` / `parking` / `furnished` columns anywhere. The brief's filters for these **cannot be built honestly** from current data. `room_types.bed_configuration` (varchar) and `room_types.max_guests` are the only capacity signals. See §0.5 Capability Map.


---

## 9. Media pipeline

- **Storage:** `public/assets/uploads` (public) and `storage/private/kyc` (private, streamed).
- **Service:** `App\Services\UploadService` (18 lines) — validates an image, converts to **WebP** (GD), returns a relative path. `image_url()` wraps with `url()`; `image_url(null, 'assets/images/placeholder-stay.svg')` fallback is used everywhere.
- **`property_images.file_url` stores a relative path** (e.g. `assets/uploads/xxx.webp`); `thumbnail_url` exists but is **read by no view**.
- **No CDN** (`CDN_URL=""`), **no on-the-fly transforms**, **no `srcset`/AVIF**, **no video, no 360°/Matterport, no floor plans** in the data model or views.
- **Performance gaps:** `property-card` requests a single full-size `file_url` at `800×600` with `loading="lazy"`; hero/eager images use `fetchpriority="high"`; `.property-card__media` has `aspect-ratio: 4/3` — a sound CLS baseline to build on.
- `composer.json` `suggest`: `ext-gd` required for server-side resizing / WebP.

---

## 10. Map provider and geocoding

- **No map provider is active.** `MAP_PROVIDER=none`, `FEATURE_MAP_PROVIDER=none` (documented options: `none|leaflet_osm|google|mapbox`).
- **No map library is vendored** (`public/assets/vendor/` contains only Font Awesome). **No geocoding** integration; no `GOOGLE_MAPS_API_KEY` / `MAPBOX_TOKEN` values.
- **`properties.latitude` / `longitude` are real, indexed and populated**, alongside `landmark`, `ward` and `district` — map *data* exists, so a provider can be switched on later with **zero schema work**.
- CSP would require `connect-src` / `script-src` / `img-src` additions before any third-party tile provider could load (`security.csp_connect_extra`, `csp_script_extra`, `csp_img_src` are already configurable).
- **Redesign consequence:** the "Map" result mode is built as a real, provider-driven surface that renders an honest *unavailable* state while `map_provider=none`, and delivers genuine location value today from real data (region/district clustering with real counts, plus per-property "open in maps" deep links built from the stored coordinates). Nothing is fabricated.


---

## 11. Payments and bookings

- **Providers:** `App\Payments\PaymentGatewayInterface` + **`MockGateway` only**. `config/payments.php` and `.env` scaffold M-Pesa, Mixx by Yas, Airtel Money, Halopesa and cards — **every one disabled** (`PAYMENT_*_ENABLED=false`, `FEATURE_LIVE_PAYMENTS=false`, `FEATURE_MOCK_PAYMENTS=true`, `PAYMENT_DEFAULT_GATEWAY=mock`).
- **Flow:** search → property → quote (`BookingService::quote`) → `POST /bookings/hold` (`BookingService::hold`; hold expires per `BOOKING_HOLD_MINUTES=15`) → `/checkout/{reference}` → `POST /checkout/{reference}/pay` (`PaymentService::pay`) → booking confirmed or an explicit simulation outcome.
- **Money safety:** all arithmetic in **integer minor units** (`Money::minor` / `Money::decimal`); `booking_price_snapshots` records the server-computed quote; **client-supplied amounts are never trusted** (only `room_id`, dates, guests and rooms are accepted, then re-quoted server-side).
- **Idempotency:** `payment_events.provider_event_id` is **UNIQUE** — the primary guard; `processed` / `processed_at` track consumption.
- **Supporting tables:** `payments`, `refunds`, `disputes`, `financial_ledger`, `host_settlements`, `settlement_items`, `discounts`, `discount_usages`, `affiliates`, `affiliate_referrals`, `host_subscriptions`, `subscription_plans`, `service_providers`, `service_categories`, `services`, `service_orders`.
- **Commission:** `COMMISSION_DEFAULT_PERCENT=10.00`, clamped 0–35, overridable per host/property via `settings` rows.
- **Availability integrity:** `HostService::inventory()` refuses to remove already-booked inventory or exceed room quantity; `SearchRepository` counts overlapping confirmed/completed/disputed bookings plus unexpired pending holds.
- **Currency at checkout:** only `TZS` and `USD` are valid on `room_types.currency`; conversion uses `exchange_rates` plus `config/currency.php` fallbacks. KES/EUR/GBP/UGX/AED appear in `currency.supported` but are **not valid storage currencies**.
- **No webhook HTTP endpoint is routed** — `PaymentGatewayInterface` defines the contract and `payment_events` is ready, but there is no `POST /webhooks/*` route. Flagged as a gap; must not be invented.

---

## 12. Dashboards and roles

| Role | Entry | Controller | Backing queries |
|---|---|---|---|
| Guest / traveller | `/guest`, `/guest/bookings`, `/guest/favourites`, `/guest/profile` | `GuestController@index` (one view for all four) | bookings + property name, favourites, notifications, saved_searches, user |
| Host / operator | `/host`, `/host/properties`, `/host/bookings` | `HostController@index` | properties, bookings, `host_kyc`, `FinanceService::balances`, `host_settlements`, revenue grouped by currency |
| Host — create/edit | `/host/properties/create`, `/host/properties/{id}/edit` | `HostController@create` / `@edit` | `HostService::property`, room_types, property_images |
| Host — mutations | `POST /host/properties/{id}/actions` | `HostController@action` | `room` · `inventory` · `submit` · `image` |
| Admin | `/admin`, `/admin/content`, `/admin/actions`, `/admin/kyc/{id}/document` | `AdminController` | counts, properties, kyc, payments, balances, settlements, reconciliation mismatches, users, audit trail |
| Agency / team | — | — | **No agency or team model exists** (no agency role; no `teams`, `members` or `lead_assignments` tables) |

**Verification gates (real and enforced):** a property cannot be submitted without verified host KYC **and** ≥1 image **and** ≥1 active room type; an admin cannot publish without a verified host, image and active room; hosts can never self-verify; editing a published listing returns it to `draft` + `pending`; a reviewer may not review their own KYC.

---

## 13. SEO implementation

**Present and working**
- `GET /robots.txt` from `SeoController@robots` using `config/seo.robots.disallow` (`/admin`, `/host/`, `/guest/`, `/checkout`, `/api/`, `/login`, `/register`, `/password/`, `/health`, `/search?`).
- **4 sitemaps:** index (`/sitemap.xml`) plus `sitemap-properties.xml` (`DatabaseSitemap::propertyUrls()`, published only), `sitemap-destinations.xml` (one URL per real region) and `sitemap-pages.xml` (7 static URLs).
- `partials/seo-head.php`: `charset`, `viewport`, `language`, `<title>`, `description`, `robots`, `canonical`, OG (`title`, `description`, `image`, `type`, `locale`, `site_name`) and Twitter card — **but OG/Twitter tags are only emitted when a per-page `metaImage` exists and is not the default**.
- Per-route `metaTitle` / `metaDescription` are set in every controller.
- Property pages are **fully server-rendered** — correct for crawlability (ISR is neither available nor needed in this stack).

**Missing (redesign deliverable)**
- **Zero Structured Data.** No `application/ld+json` anywhere (grep-verified): no `RealEstateListing`, `Offer`, `Place`, `PostalAddress`, `Organization`, `RealEstateAgent`, `BreadcrumbList`, `AggregateRating`.
- **Broken canonical:** the homepage renders `<link rel="canonical" href="">` (live-verified) because `current_request()?->url()` resolves empty and no controller populates `$canonical`.
- **No `hreflang`**, despite `lang/en` + `lang/sw` being real.
- **No `og:url`** and no Twitter fallback on pages without images.
- **No redirect register** — the brief requires 301/308 preservation for changed URLs; none exists.
- **Do not add** `AggregateRating` unless `review_count > 0` and the average is real.


---

## 14. i18n / currency / timezone

| Aspect | Finding |
|---|---|
| Locales | `en` (default) + `sw` are **real**, with `resources/lang/{en,sw}/ui.php`; `config/locale.php` lists both with labels/native/flags |
| Helpers | `t('key')` (flat) and `__('dotted.key', $replace)` (nested + `:placeholder`) |
| Middleware | `SetLocale` + `Session::get('locale')`; `app_locale()` drives `<html lang>`; `$GLOBALS['stayin_locale']` |
| Coverage | **Partial and inconsistent.** Auth views use `__('auth.*')`. All discovery views (`home`, `search`, `stays/*`, `layouts/app`, `errors/*`, `guest`, `host`, `admin`, `checkout`, `pages/*`) hard-code English |
| Bilingual data | `properties.description_sw`, `hero_slides.title_sw/description_sw`, `pages.title_sw/body_sw`, `notifications.title_sw/message_sw` exist — the **data layer is bilingual, the view layer is not** |
| RTL | No RTL support (only incidental `inset-inline` usage); requires new work |
| Currency | Storage: TZS + USD only (`room_types.currency` ENUM). Display: `format_money()` + `config/pricing.symbols`. `config/currency.php` declares 6 currencies; `pricing.supported = ['TZS','USD']` |
| FX | `FX_PROVIDER=manual` with fallbacks `USD_TZS=2643.562101`, `TZS_USD=0.000378`; `exchange_rates` table exists; `FEATURE_LIVE_FX_RATES=false` |
| Units | **None** — no m²/ft² anywhere (no area columns) |
| Timezone | `APP_TIMEZONE=UTC` (storage/logic), `APP_DISPLAY_TIMEZONE=Africa/Dar_es_Salaam` (display, shared as `$displayTimezone` via `ShareViewData`) |
| Phone | A `phone` validation rule exists; **no per-country formatting or mask** |
| Dates | Raw ISO `Y-m-d` printed directly (`$booking['check_in']`) — no locale-aware formatting helper |

---

## 15. Notifications

- **In-app:** `notifications` table (`type`, `title_en/sw`, `message_en/sw`, `data` JSON, `channel` ENUM(in_app,email,sms), `is_read`, `read_at`); `POST /guest/notifications/read`; the list is surfaced by `GuestController@index`.
- **Email:** `email_queue` + `MailService::queue()`; transports `log` (default) and native SMTP; `phpmailer` optional/suggested; `MAIL_DEV_REDIRECT` supported. Templates `emails/layout` and `emails/verify-code`.
- **SMS:** the column exists (`channel='sms'`) but `FEATURE_SMS_NOTIFICATIONS=false` and **no SMS transport exists**.
- **WhatsApp:** `FEATURE_WHATSAPP_DEEPLINK=true` (`WHATSAPP_SUPPORT_NUMBER`) and `FEATURE_WHATSAPP_API=false` (no transport). No WhatsApp Business integration.
- **Push:** `FEATURE_PUSH_NOTIFICATIONS=false`; no VAPID keys and no push handler in the service worker.
- `App\Services\NotificationService` is a **10-line stub**.
- **Per-channel / per-event notification preferences do not exist** (`newsletter_consents` covers marketing consent only).

---

## 16. Analytics and error tracking

| Capability | Status |
|---|---|
| Product analytics | **None.** `ANALYTICS_FIRST_PARTY=true` is declared but there is **no tracking code, no event bus, no endpoint and no table** (grep-verified: no `gtag`, no `dataLayer`, no analytics JS) |
| GA4 | Not loaded; `GA4_MEASUREMENT_ID` absent from `.env` |
| Error tracking | **None.** No Sentry/Rollbar equivalent; no source maps (no build step) |
| RUM / Core Web Vitals | **None** |
| Audit trail | **Real and used.** `audit_logs` (`action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `user_agent`) written via `FinanceService::audit()` for KYC decisions, property moderation, user suspension, commission changes, CMS updates, settlement transitions and KYC document views |
| Logging | `App\Core\Logger` + `LOG_LEVEL=debug`; `storage/logs` |
| Reconciliation | `FinanceService::reconcile()` detects ledger mismatches and surfaces them to admins as `mismatches` |

**Redesign consequence:** the brief's §29 taxonomy is implemented as a **new, provider-agnostic event contract** (`surface.entity.action`) with a single dispatcher and `data-*` instrumentation, plus a first-party sink. Adding a third-party provider is explicitly **not authorised** by the brief ("Do not hard-code a new analytics provider without instruction").

---

## 17. Responsive breakpoints and mobile navigation

- Breakpoints authored in CSS: **320 / 360 / 390 / 430 / 720 / 860 / 1100 / 1440**.
- `.container { width: min(var(--container), 100% - 2×gutter) }`, `--container: 1200px` → **1320px at ≥1440px**. The brief's 1440 max / 1280–1360 content measure will be adopted.
- **Mobile navigation is a collapsed desktop menu**: ≤860px the same `<nav>` becomes a fixed dropdown drawer under the header (`visibility/opacity/transform`), toggled by `#navToggle` with `aria-expanded`, Escape and outside-click handling. This **violates brief §4** ("purpose-built, not a collapsed desktop menu").
- **`.bottom-nav` CSS exists but no markup renders it** (5-column fixed bottom bar with `aria-current` styling at ≤720px) — useful scaffolding left unused.
- `--nav-height: 80px` (60px ≤360px), `--page-gutter: clamp(1rem,3vw,3rem)`, `--space-section: clamp(3rem,1.75rem+4vw,6rem)`.
- `.mobile-booking` sticky bottom CTA exists for property pages at ≤860px.
- `body { padding-block-end: env(safe-area-inset-bottom) }`; `.site-main { padding-block-end: 76px }` at ≤720px for bottom-nav clearance.
- **Touch targets:** `.btn` is `min-height: 48px` (good); `.btn--sm` is `min-height: 34px` (**below** the 44px floor); `.nav__link` uses `8px 12px` padding (**below** the floor); `.bottom-nav a` padding `6px 4px` (**below** the floor); `.theme-toggle` is 44×44 (compliant).
- **No 1920px tier** and no tablet-specific tier between 1100 and 1440.


---

## 18. Functionality that must not break (frozen contracts)

### Routes and their names
All 50 web routes and 7 API routes, including every named route referenced by `Router::url()` and the `[A-Z0-9-]` / `[0-9]+` / `[a-f0-9]{64}` regex constraints. New UI must generate URLs through the existing route names and patterns.

### Backend field names and schemas
All 56 tables, including `properties`, `room_types`, `property_images`, `availability_blocks`, `bookings`, `booking_price_snapshots`, `booking_status_history`, `reviews`, `favourites`, `saved_searches`, `notifications`, `payments`, `payment_events`, `refunds`, `disputes`, `financial_ledger`, `host_settlements`, `settlement_items`, `host_kyc`, `users`, `audit_logs`, `pages`, `hero_slides`, `rate_limits`, `settings`, `email_queue`, `password_resets`, `email_verifications`, `exchange_rates` — **column names unchanged**.

### Business logic and invariants
1. Money maths stays in integer minor units (`Money::minor` / `decimal`) — never floats.
2. Server-side re-quote on every booking; hidden client amounts are re-derived.
3. Hold expiry semantics (`BOOKING_HOLD_MINUTES`, `expires_at`).
4. Availability integrity checks in `HostService::inventory()`.
5. Search filter whitelist, non-scalar rejection, price-range validation and the currency-forcing rule (TZS/USD only; TZS forced when sorting or filtering by price without an explicit valid currency).
6. Publish gate: verified host + ≥1 image + ≥1 active room; `verification_status='verified'` only via admin action.
7. Editing a published listing resets it to `draft` + `pending`.
8. A reviewer cannot review their own KYC; privileged accounts need a separate process.
9. `provider_event_id` UNIQUE idempotency on payment events.
10. CSRF on all POST; throttles on login / register / password_reset / api / search / payment_init / hold_create.
11. Rate limits enforced server-side, never in JS.
12. Session-based auth with role + permission gates; no client-side authorisation.
13. SEO: `robots.txt`, 4 sitemaps, per-route meta, published-only URL emission.
14. KYC documents are never served from the web root.
15. Service worker + `manifest.webmanifest` + `offline.html`.
16. The CSP (`script-src 'self'` — **inline `<script>` and inline event handlers are blocked**) and the other 7 security headers.

### Defects broken *today* (must be repaired, not preserved)
These are defects, not features. Repairing them is required by §46 (regression) and §43 (business journeys must work).

| # | Defect | Evidence |
|---|---|---|
| B1 | Sign-in links `/password/forgot`; the real route is `/forgot-password` | `auth/login.php:46` → 404 |
| B2 | Host dashboard links `/host/properties/new`; real route is `/host/properties/create` | `host/index.php:11` → 404 |
| B3 | Host nav links `/host/analytics`, `/host/reviews`, `/host/settings` — no such routes | `host/index.php:38,40,41` → 404 |
| B4 | Guest nav links `/guest/wishlist`, `/guest/reviews`; the real route is `/guest/favourites` (reviews are a POST action) | `guest.php:17,18` → 404 |
| B5 | Admin nav links `/admin/properties`, `/admin/users`, `/admin/bookings`, `/admin/payments`, `/admin/reviews` — only `/admin` exists | `admin/index.php:31-35` → 404 |
| B6 | `checkout.php` posts to `/checkout` (no route) with `subtotal`/`taxes`/`total`; the controller supplies `booking` + `quote`, not `$checkout`, and passes no `$step` | `checkout.php` vs `BookingController@showCheckout` |
| B7 | `GuestController@index` passes `bookings`/`saved`/`notifications`/`searches`/`user`; the view reads `$upcomingBookings`, `$pastBookings`, `$booking['total']`, `$booking['property_name']` | `guest.php` vs `GuestController` |
| B8 | `HostController@index` passes `properties`/`bookings`/`kyc`/`balances`/`settlements`/`revenue`; the view reads `$hostData['stats']`, `$hostData['recent_bookings']` → dashboard renders empty | `host/index.php` vs `HostController` |
| B9 | `AdminController@index` passes `counts`/`properties`/`kyc`/`payments`/…; the view reads `$stats` → KPIs render empty | `admin/index.php` vs `AdminController` |
| B10 | `host/property.php` posts a **legacy field set** (`title`, `city`, `price_per_night`, `bedrooms`, `bathrooms`, `sleeps`) that `HostService::save()` does not accept → all host listing creation/editing fails validation | `host/property.php` vs `HostService::save` |
| B11 | `BookingController@receipt` renders view `receipt`, which does not exist | no `resources/views/receipt.php` |
| B12 | `AdminController@content` renders view `admin/content`, which does not exist | no `resources/views/admin/content.php` |
| B13 | Search property-type options (`hotel, apartment, villa, lodge, guesthouse, resort`) do not match the ENUM (`guesthouse`/`resort` are invalid; `guest_house`, `serviced_apartment`, `homestay`, `chumba_kimoja` are unreachable) → dead filter | `search.php:17` |
| B14 | Inline `onclick` / `onerror` handlers violate the app's own CSP (`script-src 'self'`) | `partials/flash-status.php:18`, `layouts/app.php:46` |
| B15 | Homepage canonical renders empty (`href=""`) | live HTML fetch |
| B16 | `guest.php` uses non-existent Tailwind classes | `guest.php:28` |
| B17 | ~20 CSS classes referenced by views are undefined (§3) | grep of `public/assets/css` |


---

## 0.5 Capability Map — brief requirements vs. what the data actually supports

Per §44 ("no fake data — ever") and §5 ("do not force sections the content cannot support"), the brief is reconciled against real capability. **Nothing below is fabricated; every "not supported" item is left as a clearly-flagged, backend-dependent gap.**

| Brief requirement | Verdict | Basis |
|---|---|---|
| Transaction tabs **BUY · RENT · SHORT STAY** | **SHORT STAY only** — implemented as the real nightly-stay vertical | Inventory is nightly (`room_types.base_price`, `availability_blocks` per date, `bookings.check_in/out`). There is no sale price, title, ownership or lease model. Presenting Buy/Rent would be dishonest. The segmented control instead exposes real axes: **stay type (property type)** and **region** |
| Filters: bedrooms, bathrooms, floor area, land size, parking, furnished, accessibility | **Not supported** | No such columns. Real substitutes implemented: **guests, rooms, price range, currency, rating, verified, property type, region, dates, sort** |
| Location autocomplete on cities / neighbourhoods / landmarks / developments / property names / IDs | **Partially supported** — built from real `region`, `district`, `ward`, `landmark`, `name`, `slug` values | `GET /api/v1/regions` exists; place names live on `properties`. No gazetteer or geocoder table |
| Regional suggestions (Masaki, Oyster Bay, Mbezi Beach, Zanzibar) | **Not in data** — suggestions are generated from the **20 real regions** | Dar es Salaam districts present in data are `Kigamboni` and `Ubungo`. No Zanzibar rows exist |
| Semantic / natural-language AI search, query understanding, recommendations, price intelligence, image tagging, duplicate detection | **New, backend-dependent** | No NLP/vector/ML service, no embeddings, no `price_history`, no image-tagging model. **Zero-result recovery UI** (relaxation suggestions) *is* derivable from real filter state and will be built; the ML-backed parts are documented as backend gaps and are **not** faked |
| Map modes Grid / Map / Split | **Grid + Split + provider-aware Map** with an honest unavailable state | `map_provider=none`, no map library vendored. Coordinates are real |
| Video tour, 360°, Matterport, floor plans | **Not supported** | No media model beyond `property_images`; no floor-plan table |
| Collections, compare 2–4, price/m² | **Collections: yes** (real `favourites` + `saved_searches`). **Compare: yes, for real fields only.** **Price/m²: not possible** | No area columns |
| Short-stay instant booking | **Supported** | `availability_blocks` + hold + mock payment. "Instant book" is a per-room capability, not a flagged column |
| Agency / team workspace, lead routing, CRM, SLA badges | **Not supported** | No agency/team/lead tables. The host workspace is rebuilt around what exists: listings, inventory/calendar, bookings, revenue, settlements, KYC, reviews |
| E-signature, document vault | **KYC document only** | `host_kyc.document_path`. Hosts and admins can document-verify; buyers cannot upload |
| Payments: M-Pesa, Tigo/Mixx, Airtel, Halopesa, cards | **Adapter contracts and config exist, all disabled; MockGateway is the only live gateway** | `config/payments.php`, `.env`. Checkout renders the mock flow with an explicit, unmissable test-mode disclosure and named provider slots that activate from config |
| Webhooks | **Contract only — no receiving route** | Flagged as a gap; must not be invented |
| Notifications: email, SMS, WhatsApp, push | **Email (queue + SMTP) and in-app are real**; WhatsApp deep-link real; SMS, WhatsApp API and push disabled | `config/features.php` |
| PWA: manifest, SW, offline page | **Real** | `manifest.webmanifest`, `service-worker.js`, `offline.html` |
| CMS-driven editorial | **`pages` + `hero_slides` tables and `/admin/content` exist** — the view `admin/content` is missing (B12) and is a build target | `AdminController@content` |
| Locales en + sw, RTL-ready | **en + sw real**; RTL requires new work | `resources/lang/*` |
| Units m²/ft² | **Not supported** | No area columns |
| Scheduling with ICS / Google / Outlook export | **Backend-adjacent gap** | Availability is per-room-per-date and real; calendar export needs a new endpoint. Built only as far as real data allows |
| Push alerts for saved searches | **Gap** | No push infrastructure; `saved_searches` are real, but alert delivery needs a worker |


---

## 0.6 Real data available (drives every honest design decision)

**Published inventory by region (42 published properties):**
Pwani 5 · Kilimanjaro 4 · Katavi 3 · Lindi 3 · Dodoma 3 · Simiyu 3 · Arusha 3 · Iringa 2 · Geita 2 · Njombe 2 · Tabora 2 · Mwanza 2 · Mbeya 1 · Singida 1 · Morogoro 1 · Mara 1 · Mtwara 1 · Manyara 1 · Dar es Salaam 1 · Kigoma 1

**Published inventory by type:** lodge 6 · guest_house 6 · homestay 5 · chumba_kimoja 5 · villa 5 · apartment 5 · hotel 5 · serviced_apartment 5

**Pricing:** TZS 15,000 → 375,000 per night (all 124 active room types are TZS). Mean ≈ TZS 133,177.

**Trust signals:** `verification_status='verified'` on published rows; `featured=1` flags; `rating` + `review_count` real (0.00–5.00, counts 0–3).

**Photography:** 199 `property_images` rows with real `file_url`s — the platform's genuine photography must carry the redesign (no stock imagery, no gradients standing in for photos).

**Content:** 5 `pages` rows (about/contact/privacy/terms/help), 8 active `hero_slides`, `notifications`, `reviews`.

---

## 0.7 Design-system gap analysis (what the redesign must add)

| Brief pillar | Today | Gap |
|---|---|---|
| Premium editorial feel | Limestone/brown palette, serif display, generous whitespace | Palette must move to the brief's architectural forest green + warm ivory + champagne accent; hierarchy re-tuned with a `clamp()` fluid scale |
| Intelligent search | Single GET form, 12 filters, 24/page | No autocomplete, no active-filter chips, no zero-result recovery, no saved-search UI, dead property-type options (B13) |
| Trust | `verified` badges on card + detail, real reviews, KYC pipeline | No reviewer identity, no response-time signal, no verification explainer, no review moderation UI |
| Conversion | Booking card + `mobile-booking` anchor | No save/favourite UI, no share, no inquiry parity desktop↔mobile, no sticky action panel, no toasts |
| Motion | 3 transition tokens, card hover zoom | No entrance animation, no micro-interactions, no skeleton system, no scroll reveal |
| States | `.empty-state`, 5 error pages | No skeletons, no per-widget loading, no image-load failure handling, no toast markup |
| Accessibility | Skip link, `focus-visible`, aria-live announcer, `aria-expanded` nav, `sr-only` | 34px buttons and 8px nav padding below the 44px floor; inline handlers break CSP; `<table class="table">` unstyled; no focus trap in the photo dialog; no combobox semantics |
| Performance | Lazy images, `fetchpriority`, mtime cache-busting, self-hosted subset fonts | Full-size images everywhere (no `srcset`), Font Awesome CSS + 6 webfonts loaded on every page, no route-level splitting (one JS file), no prefetch |
| SEO | robots + 4 sitemaps + per-route meta | No JSON-LD, broken canonical, no hreflang, no `og:url`, no redirect register |

---

## 0.8 Iconography decision (recorded)

The brief mandates **one** icon set at 1.5–2px stroke (Lucide/Phosphor) and **no emoji as UI**. The app ships **Font Awesome 6 Free** (CSS + 6 webfonts, vendored). Plan: introduce a **self-hosted inline-SVG icon helper** (Lucide geometry, 1.5px stroke, `currentColor`, `aria-hidden` by default) covering exactly the icons the app uses; replace every `fa-*` usage; then drop the Font Awesome `<link>`. This removes a render-blocking stylesheet plus 6 webfonts from every page while satisfying §35 exactly. `public/assets/vendor/fontawesome` is retained on disk (nothing references it afterwards) and may be deleted in a later cleanup commit — §45 is honoured by not deleting before proving zero dependencies.

---

## 0.9 Typography decision (recorded)

The brief specifies Manrope/Plus Jakarta Sans + Inter. The app self-hosts **subset TTFs** for Fraunces (display serif) + Source Sans 3 (interface) — CSP-safe (`font-src 'self' data:`), offline-capable and FOUT-free. Plan: the **scale, weights (300–800), tracking and line-height** from §2.2 are implemented immediately (they are the actual source of the typographic upgrade) and the brief's families are placed **ahead of** the self-hosted pair in the font stack, so adopting licensed binaries later is a one-line change with zero risk today. No external font request is introduced (CSP `font-src 'self' data:` is preserved).


---

## 0.10 Deliverables and execution order adopted

Following the brief's FINAL EXECUTION ORDER, adapted to this stack:

1. **§0 audit** — this document ✔
2. **Design tokens + primitives** — `tokens.css` retuned to the brief's palette/scale; new `system.css` primitive layer; `Icon` helper; skeleton/toast/chip/drawer primitives
3. **Global layout + navigation** — desktop (brand · primary nav · search · saved · messages · profile + prominent *List Your Property* CTA), purpose-built mobile nav, real `bottom-nav` markup
4. **Homepage** — hero + search, featured, real region discovery, property types, verified collection, recently added, trust, how it works, host CTA, newsletter, premium footer (only sections the data supports)
5. **Discovery + search + filters** — URL-state filters, active chips, clear-all, live count, zero-result recovery, real property-type options, sort
6. **Property cards** — real photography, favourite, verified, price, capacity, host identity, skeleton parity
7. **Property detail** — editorial gallery + lightbox + keyboard/swipe, sticky section nav, sticky action panel, amenities, rooms, reviews, policies, host card, similar properties
8. **Inquiry / viewing / booking** — real quote → hold → checkout (mock) → receipt; repair B6/B11
9. **Auth + KYC** — split-screen using real platform photography; repair B1; add KYC status surfaces
10. **User dashboard** — repair B4/B7; recently viewed, saved, bookings, searches, notifications, profile
11. **Host / admin workspaces** — repair B2/B3/B8/B9/B10/B12; multi-step listing editor bound to `HostService` validation; moderation, KYC, finance, CMS
12. **Responsive** per §32
13. **Motion** per §27
14. **States** per §28
15. **Analytics taxonomy** per §29 (first-party, provider-agnostic)
16. **SEO + JSON-LD** per §30 (repair B15)
17. **Notifications + scheduling** per §21/§23 (real capabilities only)
18. **Payments** per §22 (mock verified end-to-end)
19. **Accessibility** per §34
20. **Responsive QA** per §32/§46
21. **Regression** per §43/§46
22. **Performance** per §36
23. **Observability** per §39 (first-party — no new vendor)
24. **Remove obsolete styling only after dependency confirmation**

---

## 0.11 Risk register

| Risk | Severity | Mitigation |
|---|---|---|
| Repairing B1–B17 changes behaviour that automated tests may assert | Medium | `tests/run.php` run before and after; changes move strictly *toward* the backend's real contract |
| Introducing a `system.css` layer could conflict with legacy selectors | Medium | The new layer is additive and scoped; legacy files keep loading; checked at 8 breakpoints |
| Replacing Font Awesome could miss an icon | Medium | Exhaustive grep of `fa-` classes; the helper logs unknown names in debug; the vendor directory stays on disk |
| CSP (`script-src 'self'`) blocks inline JS | High | All new JS lives in external files under `assets/js`; the two existing inline handlers (B14) are removed in favour of delegated listeners |
| JSON-LD under strict CSP | Low | `<script type="application/ld+json">` is a *data block*, exempt from `script-src` per the CSP spec — safe |
| Fabricating data to satisfy brief sections | High | Every brief section is mapped in §0.5; unsupported items render honest states and are documented as backend gaps |
| Deleting legacy CSS breaks an unknown view | Medium | No deletions until a full class-usage sweep is run; `docs/LEGACY_AUDIT.md` already tracks view-layer debt |

