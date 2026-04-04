<?php

namespace Workbench\App\Http\Controllers;

use Flouci\Laravel\Facades\Flouci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FlouciWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();
        $paymentId = $this->extractPaymentId($payload);

        Log::info('Flouci webhook received.', [
            'payment_id' => $paymentId,
            'payload' => $payload,
        ]);

        $verification = null;
        $error = null;

        if ($paymentId !== null) {
            try {
                $verification = Flouci::verifyPayment($paymentId);

                Log::info('Flouci webhook verification completed.', [
                    'payment_id' => $paymentId,
                    'verification' => $verification,
                ]);
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();

                Log::warning('Flouci webhook verification failed.', [
                    'payment_id' => $paymentId,
                    'error' => $error,
                ]);
            }
        } else {
            $error = 'No payment identifier was found in webhook payload.';

            Log::warning('Flouci webhook payment id missing.', [
                'payload' => $payload,
            ]);
        }

        return response()->json([
            'received' => true,
            'payment_id' => $paymentId,
            'verified' => $verification !== null,
            'verification' => $verification,
            'error' => $error,
        ]);
    }

    protected function extractPaymentId(array $payload): string|int|null
    {
        return data_get($payload, 'payment_id')
            ?? data_get($payload, 'id')
            ?? data_get($payload, 'data.payment_id')
            ?? data_get($payload, 'result.payment_id');
    }
}
