<?php

namespace Omalizadeh\MultiPayment;

use Illuminate\Support\ServiceProvider;

class MultiPaymentServiceProvider extends ServiceProvider
{
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
