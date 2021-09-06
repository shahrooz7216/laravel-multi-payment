<?php

namespace Omalizadeh\MultiPayment\Tests;

use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Facades\PaymentGateway;

class GatewayConfigTest extends TestCase
{
    /** @test */
    public function defaultGatewayIsIdentifiedTest(): void
    {
        $this->assertEquals('zarinpal', PaymentGateway::getGatewayName());
        $this->assertEquals('main', PaymentGateway::getGatewayConfigKey());
    }

    /** @test */
    public function gatewayCanBeSetTest(): void
    {
        PaymentGateway::setGateway('zarinpal.other');
        $this->assertEquals('zarinpal', PaymentGateway::getGatewayName());
        $this->assertEquals('other', PaymentGateway::getGatewayConfigKey());
    }

    /** @test */
    public function gatewayPatternWithoutDotsErrorTest(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        PaymentGateway::setGateway('zarinpal_other');
    }

    /** @test */
    public function gatewayPatternWithManyDotsErrorTest(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        PaymentGateway::setGateway('zarinpal.other.default');
    }

    /** @test */
    public function emptyOrInvalidGatewaySettingsErrorTest(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        PaymentGateway::setGateway('zarinpal.second');
    }
}
