<?php

namespace Omalizadeh\MultiPayment\Providers;

use Illuminate\Support\ServiceProvider;

class MultiPaymentServiceProvider extends ServiceProvider
{
    public function register()
    {
        parent::register();
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/online-payment.php',
            'online-payment.php'
        );
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'MultiPayment');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/online-payment.php' => config_path(
                    'online-payment.php'
                )
            ], 'config');
            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path(
                    'views/vendor/multipayment'
                )
            ], 'views');
        }
    }
}
