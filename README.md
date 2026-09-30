# Laravel Flouci

[![Tests](https://github.com/Rhaima96/laravel-flouci/actions/workflows/tests.yml/badge.svg)](https://github.com/Rhaima96/laravel-flouci/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/rhaima/laravel-flouci.svg)](https://packagist.org/packages/rhaima/laravel-flouci)
[![Total Downloads](https://img.shields.io/packagist/dt/rhaima/laravel-flouci.svg)](https://packagist.org/packages/rhaima/laravel-flouci)
[![License](https://img.shields.io/packagist/l/rhaima/laravel-flouci.svg)](https://packagist.org/packages/rhaima/laravel-flouci)

Accept [Flouci](https://flouci.com) payments (Tunisia) in Laravel: create payments, verify them, handle the
webhook with events, refund, and read the transaction history.

## Requirements

- PHP 8.2+
- Laravel 12 or 13

## Installation

```bash
composer require rhaima/laravel-flouci
```

The service provider and the `Flouci` facade are auto-discovered.

## Configuration

Get your keys from the Flouci dashboard (every developer account has a **TEST APP** for the sandbox), then set:

```env
FLOUCI_PUBLIC_KEY=
FLOUCI_PRIVATE_KEY=
FLOUCI_SUCCESS_LINK="${APP_URL}/payment/success"
FLOUCI_FAIL_LINK="${APP_URL}/payment/fail"
FLOUCI_WEBHOOK_URL="${APP_URL}/flouci/webhook"
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=flouci-config
```

| Option | Env | Default | Description |
|---|---|---|---|
| `base_url` | `FLOUCI_BASE_URL` | `https://developers.flouci.com/api` | API base URL |
| `public_key` | `FLOUCI_PUBLIC_KEY` | | Public key |
| `private_key` | `FLOUCI_PRIVATE_KEY` | | Private key |
| `success_link` | `FLOUCI_SUCCESS_LINK` | | Default redirect after a successful payment |
| `fail_link` | `FLOUCI_FAIL_LINK` | | Default redirect after a failed payment |
| `webhook` | `FLOUCI_WEBHOOK_URL` | | Default `webhook` sent with each payment |
| `card_payment` | `FLOUCI_CARD_PAYMENT` | `true` | Default `accept_card` (Flouci's own default is `false`) |
| `image_url` | `FLOUCI_IMAGE_URL` | | Default image shown on the payment page |
| `session_timeout` | `FLOUCI_SESSION_TIMEOUT` | Flouci: 1200 | Payment session duration in seconds (`session_timeout_secs`) |
| `merchant_id` | `FLOUCI_MERCHANT_ID` | | Default `merchant_id` for `transactionHistory()` |
| `timeout` | `FLOUCI_TIMEOUT` | `15` | HTTP timeout in seconds |

## Usage

### Create a payment

```php
use Flouci\Laravel\Facades\Flouci;

$payment = Flouci::generatePayment([
    'amount' => 10000,                  // in millimes: 10000 = 10 TND
    'developer_tracking_id' => 'order_1001',
]);

return redirect()->away($payment['result']['link']);
```

Any Flouci field can be passed per call and overrides the config defaults
(`success_link`, `fail_link`, `webhook`, `accept_card`, `image_url`, `session_timeout_secs`).

### Verify a payment

Flouci appends `payment_id` to your success and fail links. Always verify it server-side:

```php
use Flouci\Laravel\Enums\PaymentStatus;

$verification = Flouci::verifyPayment($request->query('payment_id'));
$status = PaymentStatus::fromVerification($verification);

if ($status?->isPaid()) {
    // mark the order as paid
}
```

`PaymentStatus` cases: `Success`, `Pending`, `Expired`, `Failure`, `PreauthSuccess`, `SystemFailure`.
`isFinal()` returns `false` for `Pending` and `PreauthSuccess`.

### Webhook

Register the route (CSRF protection is removed automatically, so `routes/web.php` works):

```php
Route::flouciWebhook();                       // GET|POST /flouci/webhook, named flouci.webhook
Route::flouciWebhook('payments/flouci/hook'); // custom URI
Route::flouciWebhook()->middleware('throttle:60,1');
```

Flouci calls it with `GET ?payment_id=...&success=True|False` and does not sign the request. The package
only trusts the `payment_id`, reads the real status from `verifyPayment()`, then dispatches an event:

| Flouci status | Event |
|---|---|
| `SUCCESS` | `Flouci\Laravel\Events\PaymentSucceeded` |
| `FAILURE`, `SYSTEM_FAILURE` | `Flouci\Laravel\Events\PaymentFailed` |
| `EXPIRED` | `Flouci\Laravel\Events\PaymentExpired` |
| `PENDING`, `PREAUTH_SUCCESS` | none |

```php
use Flouci\Laravel\Events\PaymentSucceeded;

Event::listen(function (PaymentSucceeded $event) {
    $order = Order::where('reference', $event->trackingId())->firstOrFail();

    if ($order->amount_millimes !== $event->amount()) {
        return; // unexpected amount: do not mark the order as paid
    }

    $order->markAsPaid($event->paymentId);
});
```

Each event exposes `paymentId`, `status` (`PaymentStatus`), `verification` (raw response), `trackingId()` and
`amount()`. To handle every case in one listener, type-hint the `FlouciPaymentEvent` interface.

Good to know:

- Flouci retries the webhook until it gets a 2xx response. A replayed webhook dispatches its event only once
  (24h cache key): use a shared cache store (Redis, database) in production and keep an idempotency check
  on your order.
- If the Flouci API is unreachable during verification, the route answers 5xx so Flouci retries.
- Without a `payment_id`, the route answers `422`.

### Refund

```php
$refund = Flouci::refund($paymentId); // full refund
```

Only completed, not yet refunded payments can be refunded. A refused refund always throws a
`FlouciException`, even when Flouci answers HTTP 200 with `"status": "error"`.

### Transaction history

```php
$history = Flouci::transactionHistory([
    'start_date' => '2026-09-01T00:00:00Z', // ISO-8601 strings
    'end_date' => '2026-09-30T23:59:59Z',
    'type' => 'online',                     // or pos
]);
```

Parameters are sent as-is to `GET /api/developers/history`
([docs](https://docs.flouci.com/api-reference/transaction-history)). `merchant_id` is required: pass it in the
query or set `FLOUCI_MERCHANT_ID`.

### Errors

Every failure (HTTP error, timeout, unreadable response) throws a `FlouciException`:

```php
use Flouci\Laravel\Exceptions\FlouciException;

try {
    $payment = Flouci::generatePayment(['amount' => 10000]);
} catch (FlouciException $e) {
    $e->getCode();                         // HTTP status, 0 on network errors
    $e->response?->json('result.message'); // Flouci response body
}
```

## Testing your app

Fake the Flouci API with Laravel's HTTP client:

```php
Http::fake([
    'developers.flouci.com/api/v2/generate_payment' => Http::response([
        'result' => ['success' => true, 'payment_id' => 'abc', 'link' => 'https://checkout.flouci.com/abc'],
    ]),
]);
```

Test cards for the Flouci sandbox are listed in the [Flouci docs](https://docs.flouci.com/essentials/testing).

## Contributing

```bash
composer test     # Pest
composer lint     # Pint
composer analyse  # Larastan
```

To try the package against the real Flouci sandbox:

```bash
cp workbench/.env.example workbench/.env        # add your TEST APP keys
cloudflared tunnel --url http://localhost:8000  # then set the https URL as APP_URL
vendor/bin/testbench serve --port=8000
```

Open `<APP_URL>/flouci/sandbox` and pay with a test card. Webhook calls and dispatched events are logged to
`vendor/orchestra/testbench-core/laravel/storage/logs/laravel.log`.

## Security

See [SECURITY.md](SECURITY.md).

## License

MIT. See [LICENSE](LICENSE).
