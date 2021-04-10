<?php

namespace Omalizadeh\MultiPayment\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class MultiPaymentServiceProvider extends ServiceProvider
{
    public function register()
    {
        parent::register();
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/multipayment.php',
            'multipayment.php'
        );
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'MultiPayment');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path(
                    'views/vendor/multipayment'
                )
            ], 'views');

            $this->publishes([
                __DIR__ . '/../../config/multipayment.php' => config_path(
                    'multipayment.php'
                )
            ], 'config');

            $this->publishGateways();

        }
    }

    protected function publishGateways()
    {
        $configPath = __DIR__ . '/../../config/';
        $list = scandir($configPath, SCANDIR_SORT_NONE);
        $gateways = Arr::where(
            $list,
            function ($file) use ($configPath) {
                return is_file($configPath . $file)
                    && preg_match('/^(gateway\_(.+))\.php$/i', $file)
                    && pathinfo(
                        $configPath . $file,
                        PATHINFO_EXTENSION
                    ) === 'php';
            }
        );
        foreach ($gateways as $gateway) {
            $this->publishes(
                [
                    $configPath . $gateway => config_path($gateway)
                ],
                basename($gateway, '.php')
            );
        }
    }
}
