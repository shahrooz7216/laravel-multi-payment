<?php

namespace Omalizadeh\MultiPayment\Tests;

use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\GatewayPayment;
use Omalizadeh\MultiPayment\Invoice;

class GatewayConfigTest extends TestCase
{
    /** @test */
    public function defaultGatewayIsIdentifiedTest()
    {
        $invoice = new Invoice(12000);
        $gatewayPayment = new GatewayPayment($invoice);
        $this->assertEquals($gatewayPayment->getGatewayName(), 'zarinpal');
        $this->assertEquals($gatewayPayment->getGatewayConfigKey(), 'default');
    }

    /** @test */
    public function gatewayCanBeSetTest()
    {
        $invoice = new Invoice(12000);
        $gatewayPayment = new GatewayPayment($invoice, 'zarinpal.other');
        $this->assertEquals($gatewayPayment->getGatewayName(), 'zarinpal');
        $this->assertEquals($gatewayPayment->getGatewayConfigKey(), 'other');
    }

    /** @test */
    public function gatewayPatternWithoutDotsErrorTest()
    {
        $this->expectException(InvalidConfigurationException::class);
        $invoice = new Invoice(12000);
        $gatewayPayment = new GatewayPayment($invoice, 'zarinpal_other');
    }

    /** @test */
    public function gatewayPatternWithManyDotsErrorTest()
    {
        $this->expectException(InvalidConfigurationException::class);
        $invoice = new Invoice(12000);
        $gatewayPayment = new GatewayPayment($invoice, 'zarinpal.other.default');
    }

    /** @test */
    public function emptyOrInvalidGatewaySettingsErrorTest()
    {
        $this->expectException(InvalidConfigurationException::class);
        $invoice = new Invoice(12000);
        $gatewayPayment = new GatewayPayment($invoice, 'zarinpal.second');
    }
}
