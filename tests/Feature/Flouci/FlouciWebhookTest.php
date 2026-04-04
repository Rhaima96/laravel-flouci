<?php

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config()->set('flouci.public_key', 'public_test_key');
    config()->set('flouci.private_key', 'private_test_key');
    config()->set('flouci.base_url', 'https://developers.flouci.com/api');
});

it('accepts a flouci webhook and verifies the payment', function () {
    Log::spy();

    Http::fake([
        'developers.flouci.com/api/v2/verify_payment/webhook_4242' => Http::response([
            'result' => [
                'status' => 'SUCCESS',
                'developer_tracking_id' => 'order_1002',
            ],
        ]),
    ]);

    $this->postJson(route('flouci.webhook'), [
        'payment_id' => 'webhook_4242',
        'status' => 'success',
    ])
        ->assertOk()
        ->assertJsonPath('received', true)
        ->assertJsonPath('verified', true)
        ->assertJsonPath('payment_id', 'webhook_4242')
        ->assertJsonPath('verification.result.status', 'SUCCESS');

    Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://developers.flouci.com/api/v2/verify_payment/webhook_4242');
});

it('accepts a webhook without payment id and returns a clear error', function () {
    Log::spy();

    $this->postJson(route('flouci.webhook'), [
        'status' => 'failure',
    ])
        ->assertOk()
        ->assertJsonPath('received', true)
        ->assertJsonPath('verified', false)
        ->assertJsonPath('payment_id', null)
        ->assertJsonPath('error', 'No payment identifier was found in webhook payload.');

    Http::assertNothingSent();
});
