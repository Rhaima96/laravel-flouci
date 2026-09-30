<?php

namespace Flouci\Laravel\Http\Controllers;

use Flouci\Laravel\Enums\PaymentStatus;
use Flouci\Laravel\Events\PaymentExpired;
use Flouci\Laravel\Events\PaymentFailed;
use Flouci\Laravel\Events\PaymentSucceeded;
use Flouci\Laravel\FlouciClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WebhookController
{
    public function __invoke(Request $request, FlouciClient $flouci): JsonResponse
    {
        // Flouci does not sign webhooks: the payload is only used to find the payment id,
        // the status always comes from the verify API.
        $paymentId = $request->input('payment_id') ?? $request->input('id') ?? $request->input('data.payment_id');

        if ((! is_string($paymentId) && ! is_int($paymentId)) || $paymentId === '') {
            return response()->json(['received' => false, 'error' => 'Missing payment_id.'], 422);
        }

        $paymentId = (string) $paymentId;
        $verification = $flouci->verifyPayment($paymentId);
        $status = PaymentStatus::fromVerification($verification);

        $event = match ($status) {
            PaymentStatus::Success => PaymentSucceeded::class,
            PaymentStatus::Failure, PaymentStatus::SystemFailure => PaymentFailed::class,
            PaymentStatus::Expired => PaymentExpired::class,
            default => null,
        };

        // ponytail: dedup via cache for one day; a DB unique key is safer if the cache store is volatile
        if ($event && Cache::add("flouci:webhook:{$paymentId}:{$status->value}", true, now()->addDay())) {
            $event::dispatch($paymentId, $status, $verification);
        }

        return response()->json(['received' => true, 'status' => $status?->value]);
    }
}
