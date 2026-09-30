<?php

namespace Flouci\Laravel\Facades;

use Flouci\Laravel\FlouciClient;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array generatePayment(array $payload)
 * @method static array verifyPayment(string|int $paymentId)
 * @method static array refund(string $paymentId)
 * @method static array transactionHistory(array $query = [])
 *
 * @see \Flouci\Laravel\FlouciClient
 */
class Flouci extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FlouciClient::class;
    }
}
