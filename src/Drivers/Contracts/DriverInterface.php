<?php

namespace Omalizadeh\MultiPayment\Drivers\Contracts;

use Omalizadeh\MultiPayment\RedirectionForm;

interface DriverInterface
{
    public function purchase(): string;

    public function pay(): RedirectionForm;

    public function verify(): string;
}
