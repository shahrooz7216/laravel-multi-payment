<?php

namespace shahrooz7216\MultiPayment\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use shahrooz7216\MultiPayment\Gateway;
use shahrooz7216\MultiPayment\Invoice;
use shahrooz7216\MultiPayment\Receipt;
use shahrooz7216\MultiPayment\RedirectionForm;

/**
 * @method static RedirectionForm purchase(Invoice $invoice, ?Closure $closure = null)
 * @method static Receipt verify(Invoice $invoice)
 * @method static array refund(Invoice $invoice)
 * @method static array unverifiedPayments()
 * @method static Gateway setGateway(string $gateway)
 * @method static string getGatewayName()
 * @method static string getGatewayConfigKey()
 *
 * @see Gateway
 *
 */
class PaymentGateway extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Gateway::class;
    }
}
