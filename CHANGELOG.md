# Changelog

All notable changes to `rhaima/laravel-flouci` will be documented in this file.

The format is based on Keep a Changelog and this project follows Semantic Versioning.

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
