---
name: flouci-payments
description: Integrate Flouci payments (Tunisia) with rhaima/laravel-flouci - checkout, return pages, webhook events, refunds, transaction history and tests.
---

# Flouci Payments

## When to use this skill

Use it when adding or changing a Flouci checkout, handling Flouci return URLs or webhooks, refunding a Flouci
payment, reading the Flouci transaction history, or testing code that calls Flouci.

## 1. Configure

```env
FLOUCI_PUBLIC_KEY=
FLOUCI_PRIVATE_KEY=
FLOUCI_SUCCESS_LINK="${APP_URL}/payment/success"
FLOUCI_FAIL_LINK="${APP_URL}/payment/fail"
FLOUCI_WEBHOOK_URL="${APP_URL}/flouci/webhook"
```

Keys come from the Flouci dashboard (the TEST APP for the sandbox). Other options (`FLOUCI_CARD_PAYMENT`,
`FLOUCI_SESSION_TIMEOUT`, `FLOUCI_MERCHANT_ID`, `FLOUCI_TIMEOUT`) are optional:
`php artisan vendor:publish --tag=flouci-config` to see them.

## 2. Create the payment

Amounts are integers in millimes (`10000` = 10 TND). Send your order id as `developer_tracking_id`.

```php
use Flouci\Laravel\Facades\Flouci;

public function checkout(Order $order)
{
    $payment = Flouci::generatePayment([
        'amount' => $order->total_millimes,
        'developer_tracking_id' => (string) $order->id,
    ]);

    $order->update(['flouci_payment_id' => $payment['result']['payment_id']]);

    return redirect()->away($payment['result']['link']);
}
```

Per-call overrides: `success_link`, `fail_link`, `webhook`, `accept_card`, `image_url`, `session_timeout_secs`.

## 3. Return pages

Flouci appends `?payment_id=...` to the success and fail links. Only show a result; the webhook is the source of
truth, but you may verify here too:

```php
use Flouci\Laravel\Enums\PaymentStatus;

$status = PaymentStatus::fromVerification(Flouci::verifyPayment($request->query('payment_id')));
// $status?->isPaid(), $status?->isFinal()
```

## 4. Webhook

```php
// routes/web.php
Route::flouciWebhook(); // GET|POST /flouci/webhook, CSRF removed
```

Do not write your own webhook controller. The package reads `payment_id`, verifies it through the API, dispatches
one event per payment status (replays are deduped for 24h in the cache) and answers 5xx if Flouci is unreachable
so Flouci retries.

| Status | Event |
|---|---|
| `SUCCESS` | `PaymentSucceeded` |
| `FAILURE`, `SYSTEM_FAILURE` | `PaymentFailed` |
| `EXPIRED` | `PaymentExpired` |
| `PENDING`, `PREAUTH_SUCCESS` | none |

```php
use Flouci\Laravel\Events\PaymentSucceeded;

class MarkOrderAsPaid
{
    public function handle(PaymentSucceeded $event): void
    {
        $order = Order::find($event->trackingId());

        if (! $order || $order->paid_at || $order->total_millimes !== $event->amount()) {
            return;
        }

        $order->update(['paid_at' => now(), 'flouci_payment_id' => $event->paymentId]);
    }
}
```

Listeners are auto-discovered from `app/Listeners`. For one listener handling every event, type-hint
`Flouci\Laravel\Events\FlouciPaymentEvent` (an interface) and switch on `$event->status`.
Use a shared cache store (Redis, database) in production so replay deduplication works across servers.

## 5. Refunds and history

```php
Flouci::refund($paymentId); // full refund, throws FlouciException when refused

Flouci::transactionHistory([
    'start_date' => '2026-09-01T00:00:00Z', // ISO-8601 strings
    'end_date' => '2026-09-30T23:59:59Z',
]); // needs FLOUCI_MERCHANT_ID or a merchant_id key
```

## 6. Errors

All failures throw `Flouci\Laravel\Exceptions\FlouciException`: `getCode()` is the HTTP status (0 on network
errors) and `$e->response?->json()` the Flouci body.

## 7. Tests

Never call the real API in tests:

```php
Http::fake([
    'developers.flouci.com/api/v2/generate_payment' => Http::response([
        'result' => ['success' => true, 'payment_id' => 'pay_1', 'link' => 'https://checkout.flouci.com/pay_1'],
    ]),
    'developers.flouci.com/api/v2/verify_payment/*' => Http::response([
        'result' => ['status' => 'SUCCESS', 'amount' => 10000, 'developer_tracking_id' => '1'],
    ]),
]);

$this->get('/flouci/webhook?payment_id=pay_1&success=True')->assertOk();

Event::assertDispatched(PaymentSucceeded::class); // after Event::fake([PaymentSucceeded::class])
```
