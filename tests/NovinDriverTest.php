<?php

namespace shahrooz7216\MultiPayment\Tests;

use shahrooz7216\MultiPayment\Exceptions\DriverNotFoundException;
use shahrooz7216\MultiPayment\Facades\PaymentGateway;

class NovinDriverTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $novinSettings = require __DIR__.'../../config/gateway_novin.php';

        $app['config']->set('gateway_novin', $novinSettings);
    }

    public function test_fetching_unverified_payments_throws_exception(): void
    {
        $this->expectException(DriverNotFoundException::class);
        $this->expectExceptionMessage('Driver does not implement');

        PaymentGateway::setProvider('novin', 'main')->unverifiedPayments();
    }
}
