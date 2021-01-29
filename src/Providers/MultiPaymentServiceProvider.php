<?php

namespace Omalizadeh\MultiPayment\Providers;

use Illuminate\Support\ServiceProvider;

class MultiPaymentServiceProvider extends ServiceProvider
{
    public function register()
    {
        parent::register();
        $this->mergeConfigFrom(
            __DIR__ . '/../config/online-payment.php.php',
            'online-payment.php'
        );
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/../config/online-payment.php' => config_path(
                        'online-payment.php'
                    )
                ],
                'config'
            );
        }
    }
}
