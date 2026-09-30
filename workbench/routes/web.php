<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\FlouciSandboxController;

Route::prefix('flouci/sandbox')->name('flouci.sandbox.')->group(function () {
    Route::get('/', [FlouciSandboxController::class, 'index'])->name('index');
    Route::post('/checkout', [FlouciSandboxController::class, 'checkout'])->name('checkout');
    Route::get('/success', [FlouciSandboxController::class, 'success'])->name('success');
    Route::get('/fail', [FlouciSandboxController::class, 'fail'])->name('fail');
});

Route::flouciWebhook();
