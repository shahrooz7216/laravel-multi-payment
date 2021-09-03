<?php

namespace Omalizadeh\MultiPayment\Tests;

use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Facades\PaymentGateway;

class GatewayConfigTest extends TestCase
{
    /** @test */
    public function defaultGatewayIsIdentifiedTest()
    {
        $this->assertEquals(PaymentGateway::getGatewayName(), 'zarinpal');
        $this->assertEquals(PaymentGateway::getGatewayConfigKey(), 'main');
    }

    /** @test */
    public function gatewayCanBeSetTest()
    {
        PaymentGateway::setGateway('zarinpal.other');
        $this->assertEquals(PaymentGateway::getGatewayName(), 'zarinpal');
        $this->assertEquals(PaymentGateway::getGatewayConfigKey(), 'other');
    }

    /** @test */
    public function gatewayPatternWithoutDotsErrorTest()
    {
        $this->expectException(InvalidConfigurationException::class);
        PaymentGateway::setGateway('zarinpal_other');
    }

    /** @test */
    public function gatewayPatternWithManyDotsErrorTest()
    {
        $this->expectException(InvalidConfigurationException::class);
        PaymentGateway::setGateway('zarinpal.other.default');
    }

    /** @test */
    public function emptyOrInvalidGatewaySettingsErrorTest()
    {
        $this->expectException(InvalidConfigurationException::class);
        PaymentGateway::setGateway('zarinpal.second');
    }
}
