<?php

namespace Hamitovdaniil\PManager;

use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/pmanager.php' => config_path('pmanager.php'),
        ]);
    }
}