<?php

use Flouci\Laravel\Events\PaymentEvent;
use Flouci\Laravel\Events\PaymentExpired;
use Flouci\Laravel\Events\PaymentFailed;
use Flouci\Laravel\Events\PaymentSucceeded;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Event::fake();
});

function fakeVerification(string $status): void
{
    Http::fake(['*' => Http::response(['result' => [
        'status' => $status,
        'amount' => 1250,
        'developer_tracking_id' => 'order_1002',
    ]])]);
}

it('verifies the payment and dispatches PaymentSucceeded', function () {
    fakeVerification('SUCCESS');

    $this->postJson(route('flouci.webhook'), ['payment_id' => 'pay_42', 'status' => 'FAILURE'])
        ->assertOk()
        ->assertExactJson(['received' => true, 'status' => 'SUCCESS']);

    Http::assertSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/v2/verify_payment/pay_42'));

    Event::assertDispatched(PaymentSucceeded::class, fn (PaymentSucceeded $event) => $event->paymentId === 'pay_42'
        && $event->trackingId() === 'order_1002'
        && $event->amount() === 1250);
});

it('maps failed and expired statuses to their events', function (string $status, string $event) {
    fakeVerification($status);

    $this->postJson(route('flouci.webhook'), ['payment_id' => 'pay_'.$status])->assertOk();

    Event::assertDispatched($event);
})->with([
    ['FAILURE', PaymentFailed::class],
    ['SYSTEM_FAILURE', PaymentFailed::class],
    ['EXPIRED', PaymentExpired::class],
]);

it('dispatches nothing for a pending payment', function () {
    fakeVerification('PENDING');

    $this->postJson(route('flouci.webhook'), ['payment_id' => 'pay_pending'])
        ->assertOk()
        ->assertJsonPath('status', 'PENDING');

    Event::assertNotDispatched(PaymentEvent::class);
});

it('dispatches only once when the same webhook is replayed', function () {
    fakeVerification('SUCCESS');

    $this->postJson(route('flouci.webhook'), ['payment_id' => 'pay_dup'])->assertOk();
    $this->postJson(route('flouci.webhook'), ['payment_id' => 'pay_dup'])->assertOk();

    Event::assertDispatchedTimes(PaymentSucceeded::class, 1);
});

it('rejects a webhook without payment id', function () {
    Http::fake();

    $this->postJson(route('flouci.webhook'), ['status' => 'SUCCESS'])
        ->assertUnprocessable()
        ->assertJsonPath('received', false);

    Http::assertNothingSent();
    Event::assertNotDispatched(PaymentEvent::class);
});

it('returns a server error when verification fails so the call can be retried', function () {
    Http::fake(['*' => Http::response('down', 503)]);

    $this->postJson(route('flouci.webhook'), ['payment_id' => 'pay_down'])->assertServerError();

    Event::assertNotDispatched(PaymentEvent::class);
});

it('strips CSRF protection from the webhook route', function () {
    // Laravel 12 puts ValidateCsrfToken in the web group, Laravel 13 puts PreventRequestForgery.
    $csrf = array_values(array_filter([
        ValidateCsrfToken::class,
        'Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery',
    ], 'class_exists'));

    app('router')->middlewareGroup('csrf-test', [StartSession::class, ...$csrf]);
    Route::middleware('csrf-test')->group(fn () => Route::flouciWebhook('csrf-test/flouci/webhook'));
    $route = collect(Route::getRoutes()->getRoutes())->first(fn ($route) => $route->uri() === 'csrf-test/flouci/webhook');

    expect(app('router')->gatherRouteMiddleware($route))->toBe([StartSession::class]);
});
