<?php

namespace Omalizadeh\MultiPayment\Tests;

use Omalizadeh\MultiPayment\GatewayPayment;
use Omalizadeh\MultiPayment\Invoice;

class ZarinpalSandboxTest extends TestCase
{
    /** @test */
    public function gatewaySettingsAreOverridedTest()
    {
        config([
            'gateway_zarinpal.default.merchant_id' => 'test-merchant-code',
            'gateway_zarinpal.default.mode' => 'sandbox',
        ]);
        $invoice = new Invoice(12000);
        $gatewayPayment = new GatewayPayment($invoice);
        $response = $gatewayPayment->purchase()->pay()->toArray();
        $this->assertArrayHasKey('action', $response);
        $this->assertStringContainsString("https://sandbox.zarinpal.com/pg/StartPay/", $response['action']);
    }
}
