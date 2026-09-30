<?php

namespace Workbench\App\Providers;

use Flouci\Laravel\Events\FlouciPaymentEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Http\Middleware\LogFlouciWebhook;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Global so the raw call is logged even when no route matches (wrong method, 405).
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)->pushMiddleware(LogFlouciWebhook::class);

        // Behind an HTTPS tunnel (cloudflared, expose) the app receives plain HTTP.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        Event::listen(function (FlouciPaymentEvent $event) {
            Log::info('Flouci event dispatched: '.class_basename($event), [
                'payment_id' => $event->paymentId,
                'status' => $event->status->value,
                'tracking_id' => $event->trackingId(),
                'amount' => $event->amount(),
            ]);
        });
    }
}
