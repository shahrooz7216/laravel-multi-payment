<?php

namespace shahrooz7216\MultiPayment\Drivers\Contracts;

interface RefundInterface
{
    public function refund(): array;
}
