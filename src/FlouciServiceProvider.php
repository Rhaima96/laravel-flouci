<?php

namespace Flouci\Laravel;

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
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/flouci.php' => config_path('flouci.php'),
        ], 'flouci-config');
    }
}
