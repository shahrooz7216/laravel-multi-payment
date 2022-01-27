<?php

namespace Omalizadeh\MultiPayment\Tests;

use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Facades\PaymentGateway;

class GatewayConfigTest extends TestCase
{
    public function testDefaultGatewayIsIdentified(): void
    {
        $this->assertEquals('zarinpal', PaymentGateway::getGatewayName());
        $this->assertEquals('main', PaymentGateway::getGatewayConfigKey());
    }

    public function testGatewayCanBeChanged(): void
    {
        PaymentGateway::setGateway('zarinpal.other');

        $this->assertEquals('zarinpal', PaymentGateway::getGatewayName());
        $this->assertEquals('other', PaymentGateway::getGatewayConfigKey());
    }

    public function testGatewayPatternWithoutDotsError(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        PaymentGateway::setGateway('zarinpal_other');
    }

    public function testGatewayPatternWithManyDotsError(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        PaymentGateway::setGateway('zarinpal.other.default');
    }

    public function testEmptyOrInvalidGatewaySettingsError(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        PaymentGateway::setGateway('zarinpal.second');
    }
}
