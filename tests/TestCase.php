<?php

namespace Tests;

use Flouci\Laravel\Facades\Flouci as FlouciFacade;
use Flouci\Laravel\FlouciServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [FlouciServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Flouci' => FlouciFacade::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('app.debug', true);
        $app['config']->set('view.paths', [__DIR__.'/../workbench/resources/views']);
        $app['config']->set('flouci.base_url', 'https://developers.flouci.com/api');
        $app['config']->set('flouci.public_key', 'public_test_key');
        $app['config']->set('flouci.private_key', 'private_test_key');
        $app['config']->set('flouci.success_link', 'https://merchant.test/payment/success');
        $app['config']->set('flouci.fail_link', 'https://merchant.test/payment/fail');
    }

    protected function defineRoutes($router): void
    {
        require __DIR__.'/../routes/web.php';
    }
}

