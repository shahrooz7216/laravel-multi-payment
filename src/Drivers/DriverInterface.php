<?php

namespace Omalizadeh\MultiPayment\Drivers;

use Omalizadeh\MultiPayment\RedirectionForm;

interface DriverInterface
{
    public function purchase(): string;

    public function pay(): RedirectionForm;

    public function verify(): string;
}
