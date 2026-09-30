<?php

namespace Flouci\Laravel;

use Flouci\Laravel\Http\Controllers\WebhookController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class FlouciServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/flouci.php', 'flouci');

        $this->app->singleton(FlouciClient::class, function () {
            return new FlouciClient(
                publicKey: config('flouci.public_key'),
                privateKey: config('flouci.private_key'),
                baseUrl: config('flouci.base_url'),
                successLink: config('flouci.success_link'),
                failLink: config('flouci.fail_link'),
                cardPayment: config('flouci.card_payment'),
                imageUrl: config('flouci.image_url'),
                timeout: (int) config('flouci.timeout', 15),
                webhook: config('flouci.webhook'),
                sessionTimeout: config('flouci.session_timeout') ? (int) config('flouci.session_timeout') : null,
                merchantId: config('flouci.merchant_id') ? (int) config('flouci.merchant_id') : null,
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/flouci.php' => config_path('flouci.php'),
        ], 'flouci-config');

        Router::macro('flouciWebhook', function (string $uri = 'flouci/webhook') {
            /** @var Router $this */
            // Flouci calls the webhook with GET ?payment_id=...&success=...; POST is kept for manual replays.
            return $this->match(['GET', 'POST'], $uri, WebhookController::class)
                // Laravel 13 renamed the web CSRF middleware to PreventRequestForgery (ValidateCsrfToken now extends it).
                ->withoutMiddleware([ValidateCsrfToken::class, PreventRequestForgery::class])
                ->name('flouci.webhook');
        });
    }
}
