<?php

namespace shahrooz7216\MultiPayment\Drivers\Contracts;

use shahrooz7216\MultiPayment\Invoice;
use shahrooz7216\MultiPayment\Receipt;
use shahrooz7216\MultiPayment\RedirectionForm;

interface PurchaseInterface
{
    public function purchase(): string;

    public function pay(): RedirectionForm;

    public function verify(): Receipt;

    public function setInvoice(Invoice $invoice): PurchaseInterface;
}
