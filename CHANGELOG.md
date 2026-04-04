# Changelog

All notable changes to `rhaima/laravel-flouci` will be documented in this file.

The format is based on Keep a Changelog and this project follows Semantic Versioning.

## [Unreleased]

### Added
- Flouci client with `generatePayment()` and `verifyPayment()`
- Config publishing via `flouci-config`
- Flouci facade and service provider auto-discovery
- Sandbox workbench routes for local package testing
- Webhook endpoint example for Flouci callbacks
- CI matrix for Laravel 11, 12 and 13
- Support for default `card_payment` configuration mapped to `accept_card`

### Fixed
- `accept_card=false` is now preserved in payment payloads
- `card_payment` config is now injected into the client correctly
