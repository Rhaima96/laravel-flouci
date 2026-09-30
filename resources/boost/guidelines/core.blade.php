## Laravel Flouci (rhaima/laravel-flouci)

Flouci payments for Tunisia: create, verify, refund, transaction history, and a verified webhook with events.
Use the `flouci-payments` skill for a full integration.

- Amounts are integers in **millimes**: `10000` = 10 TND. Never send dinars or floats.
- Use the `Flouci\Laravel\Facades\Flouci` facade; methods return raw Flouci arrays. The checkout URL is `$payment['result']['link']`.
- Never trust return URLs or webhook query params (`success=True`) to mark an order paid. Always call `Flouci::verifyPayment($paymentId)` and read the status with `Flouci\Laravel\Enums\PaymentStatus::fromVerification()`.
- Register the webhook with `Route::flouciWebhook()` instead of a custom route: Flouci calls it with `GET ?payment_id=...`, unsigned, and the package already verifies the payment, removes CSRF and dedupes replays.
- React to payments by listening to `PaymentSucceeded`, `PaymentFailed` or `PaymentExpired` (namespace `Flouci\Laravel\Events`). To catch all of them, type-hint the `FlouciPaymentEvent` interface, not the `PaymentEvent` parent class (Laravel never resolves listeners by parent class).
- In listeners, match `$event->trackingId()` (the `developer_tracking_id` you sent) and `$event->amount()` against the order before marking it paid, and keep the update idempotent.
- Every failure throws `Flouci\Laravel\Exceptions\FlouciException` (`getCode()` is the HTTP status, `->response` the raw response).
- In tests, fake the API with `Http::fake()` on `developers.flouci.com/api/*`; never call the real API.

@verbatim
<code-snippet name="Create a Flouci payment and redirect" lang="php">
$payment = Flouci::generatePayment([
    'amount' => $order->total_millimes,
    'developer_tracking_id' => (string) $order->id,
]);

return redirect()->away($payment['result']['link']);
</code-snippet>
@endverbatim
