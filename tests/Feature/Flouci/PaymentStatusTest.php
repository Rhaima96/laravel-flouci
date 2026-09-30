<?php

use Flouci\Laravel\Enums\PaymentStatus;

it('reads the status from a verification response', function () {
    $status = PaymentStatus::fromVerification(['result' => ['status' => 'SUCCESS']]);

    expect($status)->toBe(PaymentStatus::Success)
        ->and($status->isPaid())->toBeTrue()
        ->and($status->isFinal())->toBeTrue();
});

it('returns null for a missing or unknown status', function () {
    expect(PaymentStatus::fromVerification([]))->toBeNull()
        ->and(PaymentStatus::fromVerification(['result' => ['status' => 'WHATEVER']]))->toBeNull();
});

it('treats pending and pre-authorized payments as not final', function () {
    expect(PaymentStatus::Pending->isFinal())->toBeFalse()
        ->and(PaymentStatus::PreauthSuccess->isFinal())->toBeFalse()
        ->and(PaymentStatus::PreauthSuccess->isPaid())->toBeFalse()
        ->and(PaymentStatus::Expired->isFinal())->toBeTrue();
});
