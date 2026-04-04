<?php

use Flouci\Laravel\Exceptions\FlouciException;
use Flouci\Laravel\Facades\Flouci;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('flouci.public_key', 'public_test_key');
    config()->set('flouci.private_key', 'private_test_key');
    config()->set('flouci.base_url', 'https://developers.flouci.com/api');
    config()->set('flouci.success_link', 'https://merchant.test/payment/success');
    config()->set('flouci.fail_link', 'https://merchant.test/payment/fail');
    config()->set('flouci.card_payment', true);
    config()->set('flouci.image_url', null);
});

it('generates a payment with configured links and bearer credentials', function () {
    Http::fake([
        'developers.flouci.com/api/v2/generate_payment' => Http::response([
            'result' => [
                'payment_id' => 4242,
                'link' => 'https://pay.flouci.com/abc',
            ],
        ]),
    ]);

    $response = Flouci::generatePayment([
        'amount' => 10500,
        'developer_tracking_id' => 'order_4242',
    ]);

    expect($response['result']['payment_id'])->toBe(4242);

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://developers.flouci.com/api/v2/generate_payment'
            && $request->hasHeader('Authorization', 'Bearer public_test_key:private_test_key')
            && $request['amount'] === 10500
            && $request['developer_tracking_id'] === 'order_4242'
            && $request['success_link'] === 'https://merchant.test/payment/success'
            && $request['fail_link'] === 'https://merchant.test/payment/fail'
            && $request['accept_card'] === true
            && ! array_key_exists('image_url', $request->data());
    });
});

it('uses the configured accept_card value when payload does not override it', function () {
    config()->set('flouci.card_payment', false);

    Http::fake([
        'developers.flouci.com/api/v2/generate_payment' => Http::response([
            'result' => [
                'payment_id' => 5555,
                'link' => 'https://pay.flouci.com/config-false',
            ],
        ]),
    ]);

    app()->forgetInstance(Flouci\Laravel\FlouciClient::class);

    Flouci::generatePayment([
        'amount' => 2000,
        'developer_tracking_id' => 'order_config_false',
    ]);

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://developers.flouci.com/api/v2/generate_payment'
            && $request['accept_card'] === false;
    });
});

it('keeps accept_card false when explicitly provided in payload', function () {
    Http::fake([
        'developers.flouci.com/api/v2/generate_payment' => Http::response([
            'result' => [
                'payment_id' => 6666,
                'link' => 'https://pay.flouci.com/payload-false',
            ],
        ]),
    ]);

    Flouci::generatePayment([
        'amount' => 3000,
        'developer_tracking_id' => 'order_payload_false',
        'accept_card' => false,
    ]);

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://developers.flouci.com/api/v2/generate_payment'
            && $request['accept_card'] === false;
    });
});

it('uses configured image_url when payload does not override it', function () {
    config()->set('flouci.image_url', 'https://merchant.test/logo.png');

    Http::fake([
        'developers.flouci.com/api/v2/generate_payment' => Http::response([
            'result' => [
                'payment_id' => 7777,
                'link' => 'https://pay.flouci.com/config-image',
            ],
        ]),
    ]);

    app()->forgetInstance(Flouci\Laravel\FlouciClient::class);

    Flouci::generatePayment([
        'amount' => 4000,
        'developer_tracking_id' => 'order_config_image',
    ]);

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://developers.flouci.com/api/v2/generate_payment'
            && $request['image_url'] === 'https://merchant.test/logo.png';
    });
});

it('allows payload image_url to override config image_url', function () {
    config()->set('flouci.image_url', 'https://merchant.test/default-logo.png');

    Http::fake([
        'developers.flouci.com/api/v2/generate_payment' => Http::response([
            'result' => [
                'payment_id' => 8888,
                'link' => 'https://pay.flouci.com/payload-image',
            ],
        ]),
    ]);

    app()->forgetInstance(Flouci\Laravel\FlouciClient::class);

    Flouci::generatePayment([
        'amount' => 5000,
        'developer_tracking_id' => 'order_payload_image',
        'image_url' => 'https://merchant.test/custom-logo.png',
    ]);

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://developers.flouci.com/api/v2/generate_payment'
            && $request['image_url'] === 'https://merchant.test/custom-logo.png';
    });
});

it('verifies a payment by id', function () {
    Http::fake([
        'developers.flouci.com/api/v2/verify_payment/4242' => Http::response([
            'result' => [
                'status' => 'SUCCESS',
            ],
        ]),
    ]);

    $response = Flouci::verifyPayment(4242);

    expect($response['result']['status'])->toBe('SUCCESS');

    Http::assertSent(function (Request $request) {
        return $request->method() === 'GET'
            && $request->url() === 'https://developers.flouci.com/api/v2/verify_payment/4242'
            && $request->hasHeader('Authorization', 'Bearer public_test_key:private_test_key');
    });
});

it('fails fast when credentials are missing', function () {
    config()->set('flouci.public_key', null);

    app()->forgetInstance(Flouci\Laravel\FlouciClient::class);

    expect(fn () => Flouci::generatePayment(['amount' => 1000]))
        ->toThrow(FlouciException::class, 'Flouci credentials are missing');
});
