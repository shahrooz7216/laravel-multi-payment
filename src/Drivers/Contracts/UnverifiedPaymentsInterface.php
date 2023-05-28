<?php

namespace shahrooz7216\MultiPayment\Drivers\Contracts;

interface UnverifiedPaymentsInterface
{
    public function latestUnverifiedPayments(): array;
}
