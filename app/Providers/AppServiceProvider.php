<?php

namespace App\Providers;

use App\Contracts\StampImageStorageDriver;
use App\Services\StampStorage\StampImageStorageManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StampImageStorageManager::class, function () {
            return new StampImageStorageManager();
        });

        $this->app->bind(StampImageStorageDriver::class, function ($app) {
            return $app->make(StampImageStorageManager::class)->driver();
        });
    }

    public function boot(): void
    {
        //
    }
}
