<?php

namespace Workbench\App\Providers;

use Flouci\Laravel\Events\PaymentEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(function (PaymentEvent $event) {
            Log::info('Flouci event dispatched: '.class_basename($event), [
                'payment_id' => $event->paymentId,
                'status' => $event->status->value,
                'tracking_id' => $event->trackingId(),
                'amount' => $event->amount(),
            ]);
        });
    }
}
