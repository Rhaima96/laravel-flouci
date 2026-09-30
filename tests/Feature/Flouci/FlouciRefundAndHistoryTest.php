<?php

use Flouci\Laravel\Exceptions\FlouciException;
use Flouci\Laravel\Facades\Flouci;
use Flouci\Laravel\FlouciClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('refunds a payment', function () {
    Http::fake(['*' => Http::response(['result' => ['refund_id' => 'ref_1', 'status' => 'success'], 'status' => 'success'])]);

    expect(Flouci::refund('pay_42')['result']['refund_id'])->toBe('ref_1');

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === 'https://developers.flouci.com/api/v2/refund_payment'
        && $request->data() === ['payment_id' => 'pay_42']
        && $request->hasHeader('Authorization', 'Bearer public_test_key:private_test_key'));
});

it('throws when flouci reports a refund error in a successful response', function () {
    Http::fake(['*' => Http::response(['status' => 'error', 'message' => 'Payment cannot be refunded', 'code' => 'REFUND_NOT_ALLOWED'])]);

    expect(fn () => Flouci::refund('pay_42'))
        ->toThrow(FlouciException::class, 'Payment cannot be refunded');
});

it('fetches transaction history with the configured merchant id', function () {
    config()->set('flouci.merchant_id', '123');
    app()->forgetInstance(FlouciClient::class);

    Http::fake(['*' => Http::response(['results' => []])]);

    Flouci::transactionHistory(['start_date' => '2026-09-01T00:00:00Z']);

    Http::assertSent(fn (Request $request) => $request->method() === 'GET'
        && $request->url() === 'https://developers.flouci.com/api/developers/history?start_date=2026-09-01T00%3A00%3A00Z&merchant_id=123');
});

it('lets the query override the configured merchant id', function () {
    config()->set('flouci.merchant_id', '123');
    app()->forgetInstance(FlouciClient::class);

    Http::fake(['*' => Http::response([])]);

    Flouci::transactionHistory(['merchant_id' => 999]);

    Http::assertSent(fn (Request $request) => $request['merchant_id'] == 999);
});

it('requires a merchant id for transaction history', function () {
    Http::fake();

    expect(fn () => Flouci::transactionHistory())
        ->toThrow(FlouciException::class, 'merchant id is missing');

    Http::assertNothingSent();
});
