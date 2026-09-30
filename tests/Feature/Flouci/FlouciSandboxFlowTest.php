<?php

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('flouci.public_key', 'public_test_key');
    config()->set('flouci.private_key', 'private_test_key');
    config()->set('flouci.base_url', 'https://developers.flouci.com/api');
});

it('renders the sandbox test page', function () {
    $this->get(route('flouci.sandbox.index'))
        ->assertOk()
        ->assertSee('Tester un paiement sandbox depuis le workbench');
});

it('creates a sandbox payment and redirects to flouci', function () {
    Http::fake([
        'developers.flouci.com/api/v2/generate_payment' => Http::response([
            'result' => [
                'payment_id' => 4242,
                'link' => 'https://pay.flouci.com/test/4242',
            ],
        ]),
    ]);

    $response = $this->post(route('flouci.sandbox.checkout'), [
        'amount' => 1500,
        'developer_tracking_id' => 'sandbox_order_1',
    ]);

    $response->assertRedirect('https://pay.flouci.com/test/4242');

    Http::assertSent(function (HttpRequest $request) {
        return $request->url() === 'https://developers.flouci.com/api/v2/generate_payment'
            && $request['amount'] === 1500
            && $request['developer_tracking_id'] === 'sandbox_order_1'
            && $request['success_link'] === route('flouci.sandbox.success')
            && $request['fail_link'] === route('flouci.sandbox.fail');
    });
});

it('verifies the payment on the success return page', function () {
    Http::fake([
        'developers.flouci.com/api/v2/verify_payment/4242' => Http::response([
            'result' => [
                'status' => 'SUCCESS',
            ],
        ]),
    ]);

    $this->get(route('flouci.sandbox.success', ['payment_id' => 4242]))
        ->assertOk()
        ->assertSee('SUCCESS')
        ->assertSee('4242');

    Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://developers.flouci.com/api/v2/verify_payment/4242');
});
