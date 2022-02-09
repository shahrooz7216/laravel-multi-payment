<?php

namespace Omalizadeh\MultiPayment\Tests;

use Omalizadeh\MultiPayment\Exceptions\DriverNotFoundException;
use Omalizadeh\MultiPayment\Facades\PaymentGateway;

class NovinDriverTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $novinSettings = require __DIR__.'../../config/gateway_novin.php';

        $app['config']->set('gateway_novin', $novinSettings);
    }

    public function testUnverifiedPaymentsThrowsNotImplementedException(): void
    {
        $this->expectException(DriverNotFoundException::class);
        $this->expectExceptionMessage('Driver does not implement');

        PaymentGateway::setGateway('novin.main')->unverifiedPayments();
    }
}
