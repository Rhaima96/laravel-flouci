<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\FlouciSandboxController;
use Workbench\App\Http\Controllers\FlouciWebhookController;

Route::get('/', fn () => 'Flouci Laravel Package');

Route::prefix('flouci/sandbox')->name('flouci.sandbox.')->group(function () {
    Route::get('/', [FlouciSandboxController::class, 'index'])->name('index');
    Route::post('/checkout', [FlouciSandboxController::class, 'checkout'])->name('checkout');
    Route::get('/success', [FlouciSandboxController::class, 'success'])->name('success');
    Route::get('/fail', [FlouciSandboxController::class, 'fail'])->name('fail');
});

Route::post('/flouci/webhook', FlouciWebhookController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('flouci.webhook');
