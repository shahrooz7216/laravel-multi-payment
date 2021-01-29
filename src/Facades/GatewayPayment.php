<?php

namespace Omalizadeh\MultiPayment\Facades;

use Illuminate\Support\Facades\Facade;
use Omalizadeh\MultiPayment\OnlinePayment;

class GatewayPayment extends Facade
{
    public static function getFacadeAccessor()
    {
        return OnlinePayment::class;
    }
}
