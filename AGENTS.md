# AGENTS.md

Laravel package `rhaima/laravel-flouci` (Flouci payments). Code in `src/`, tests in `tests/` (Pest + Testbench),
local playground in `workbench/`.

## Commands

- `composer test`: Pest
- `composer lint`: Pint (`vendor/bin/pint` to fix)
- `composer analyse`: Larastan level 8

All three run in CI and must pass.

## Rules

- Supported: PHP 8.2+, Laravel 12 and 13. CI covers PHP 8.2/L12, 8.3/L13, 8.4/L13.
- Keep public APIs backward compatible within 1.x (methods return arrays; do not change return types).
- Every bug fix gets a test that fails without the fix.
- Flouci behaviour must match the real API, not guesses: the webhook is `GET ?payment_id=...&success=...`,
  unsigned. Check any webhook or API change against the real sandbox (see "Contributing" in README.md).
- Laravel resolves event listeners by class or interface, never by parent class.
- `resources/boost/` holds the Laravel Boost guideline and skill shipped to users: update them when the public API
  or the recommended usage changes.
- Update `CHANGELOG.md` for user-facing changes.
