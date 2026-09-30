<?php

namespace Flouci\Laravel\Enums;

enum PaymentStatus: string
{
    case Success = 'SUCCESS';
    case Pending = 'PENDING';
    case Expired = 'EXPIRED';
    case Failure = 'FAILURE';
    case PreauthSuccess = 'PREAUTH_SUCCESS';
    case SystemFailure = 'SYSTEM_FAILURE';

    /**
     * @param  array<string, mixed>  $verification
     */
    public static function fromVerification(array $verification): ?self
    {
        return self::tryFrom((string) data_get($verification, 'result.status'));
    }

    public function isPaid(): bool
    {
        return $this === self::Success;
    }

    public function isFinal(): bool
    {
        return ! in_array($this, [self::Pending, self::PreauthSuccess], true);
    }
}
