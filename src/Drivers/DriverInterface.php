<?php

namespace Omalizadeh\MultiPayment\Drivers;

use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;

interface DriverInterface
{
    public function purchase();

    public function pay(): RedirectionForm;

    public function verify(): Receipt;
}
