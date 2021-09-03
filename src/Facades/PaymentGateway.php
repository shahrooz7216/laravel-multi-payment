<?php

namespace Omalizadeh\MultiPayment\Facades;

use Illuminate\Support\Facades\Facade;
use Omalizadeh\MultiPayment\Gateway;

/**
 * @method static \Omalizadeh\MultiPayment\RedirectionForm purchase(\Omalizadeh\MultiPayment\Invoice $invoice, ?\Closure $closure = null)
 * @method static \Omalizadeh\MultiPayment\Receipt verify(\Omalizadeh\MultiPayment\Invoice $invoice)
 * @method static \Omalizadeh\MultiPayment\Gateway setGateway(string $gateway)
 * @method static string getGatewayName()
 * @method static string getGatewayConfigKey()
 *
 */
class PaymentGateway extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Gateway::class;
    }
}
