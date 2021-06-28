<?php

namespace Omalizadeh\MultiPayment\Tests;

use Omalizadeh\MultiPayment\Providers\MultiPaymentServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            MultiPaymentServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $zarinpalSettings = require __DIR__ . '../../config/gateway_zarinpal.php';
        $app['config']->set('gateway_zarinpal', $zarinpalSettings);
        $app['config']->set('multipayment.default_gateway', 'zarinpal.default');
        $app['config']->set('multipayment.convert_to_rials', true);
    }
}
