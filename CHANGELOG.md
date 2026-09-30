# Changelog

All notable changes to `rhaima/laravel-flouci` will be documented in this file.

The format is based on Keep a Changelog and this project follows Semantic Versioning.

## [1.4.2] - 2026-09-30

Found by testing against the real Flouci sandbox.

### Fixed
- Webhook route now accepts `GET`: Flouci calls `GET ?payment_id=...&success=...`, so the POST-only
  route answered 405 and no event was ever dispatched
- Listening to all payment events: added the `FlouciPaymentEvent` interface. Laravel resolves listeners
  by interface, so a listener type-hinting the `PaymentEvent` parent class never fired

### Changed
- The webhook only reads `payment_id` (the `id` and `data.payment_id` fallbacks were guesses)

## [1.4.1] - 2026-09-30

### Fixed
- `Route::flouciWebhook()` returned 419 on Laravel 13: the web group now uses `PreventRequestForgery`,
  which is also excluded from the webhook route

### Added (development only)
- `testbench.yaml` and `.env.example` to run the workbench sandbox with `vendor/bin/testbench serve`
- Workbench logs the raw webhook request and dispatched payment events

## [1.4.0] - 2026-09-30

### Added
- `refund()` for `POST /api/v2/refund_payment`; refund errors returned with HTTP 200 also throw `FlouciException`
- `transactionHistory()` for `GET /api/developers/history`
- `merchant_id` config option (`FLOUCI_MERCHANT_ID`) used by `transactionHistory()`

## [1.3.0] - 2026-09-30

### Added
- `Route::flouciWebhook()` macro registering a CSRF-free webhook endpoint
- Webhook controller that verifies the payment through the API and dispatches
  `PaymentSucceeded`, `PaymentFailed` or `PaymentExpired` (all extending `PaymentEvent`)
- Replayed webhooks dispatch their event only once (24h cache key)

### Removed
- Workbench-only webhook example controller, replaced by the shipped one

## [1.2.0] - 2026-09-30

### Added
- `PaymentStatus` enum with `fromVerification()`, `isPaid()` and `isFinal()`
- `webhook` config option (`FLOUCI_WEBHOOK_URL`) sent as default `webhook`
- `session_timeout` config option (`FLOUCI_SESSION_TIMEOUT`) sent as `session_timeout_secs`
- `@method` docblocks on the `Flouci` facade for IDE autocompletion

## [1.1.0] - 2026-09-30

### Removed
- Laravel 11 support: it is end-of-life and every 11.x release has unpatched security advisories. Composer keeps Laravel 11 apps on 1.0.x automatically.

### Changed
- CI matrix: PHP 8.2 / Laravel 12, PHP 8.3 and 8.4 / Laravel 13

## [1.0.1] - 2026-09-30

### Added
- `timeout` config option (`FLOUCI_TIMEOUT`, default 15 seconds)
- `FlouciException::$response` exposes the failed HTTP response; the exception code is the HTTP status

### Fixed
- `verifyPayment()` now URL-encodes the payment id
- Connection errors (timeouts, DNS) are wrapped in `FlouciException`

### Changed
- Workbench routes moved to `workbench/routes` and excluded from the dist archive
- `composer.lock` is no longer committed

## [1.0.0]

### Added
- Flouci client with `generatePayment()` and `verifyPayment()`
- Config publishing via `flouci-config`
- Flouci facade and service provider auto-discovery
- Sandbox workbench routes for local package testing
- Webhook endpoint example for Flouci callbacks (workbench only, not shipped)
- CI matrix for Laravel 11, 12 and 13
- Support for default `card_payment` configuration mapped to `accept_card`

### Fixed
- `accept_card=false` is now preserved in payment payloads
- `card_payment` config is now injected into the client correctly
