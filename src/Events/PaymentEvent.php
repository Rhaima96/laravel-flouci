<?php

namespace Flouci\Laravel\Events;

use Flouci\Laravel\Enums\PaymentStatus;
use Illuminate\Foundation\Events\Dispatchable;

abstract class PaymentEvent
{
    use Dispatchable;

    public function __construct(
        public readonly string $paymentId,
        public readonly PaymentStatus $status,
        public readonly array $verification,
    ) {
    }

    public function trackingId(): ?string
    {
        return data_get($this->verification, 'result.developer_tracking_id');
    }

    public function amount(): ?int
    {
        $amount = data_get($this->verification, 'result.amount');

        return is_numeric($amount) ? (int) $amount : null;
    }
}
