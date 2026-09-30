<?php

namespace Flouci\Laravel\Facades;

use Flouci\Laravel\FlouciClient;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<string, mixed> generatePayment(array<string, mixed> $payload)
 * @method static array<string, mixed> verifyPayment(string|int $paymentId)
 * @method static array<string, mixed> refund(string $paymentId)
 * @method static array<string, mixed> transactionHistory(array<string, mixed> $query = [])
 *
 * @see FlouciClient
 */
class Flouci extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FlouciClient::class;
    }
}
