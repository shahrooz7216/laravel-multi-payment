<?php

namespace Omalizadeh\MultiPayment;

use Carbon\Carbon;

class Receipt
{
    protected string $gatewayConfigKey;
    protected Carbon $datetime;
    protected Invoice $invoice;
    protected string $gatewayName;
    protected string $referenceId;

    public function __construct($referenceId, Invoice $invoice, string $gatewayName, string $gatewayConfigKey)
    {
        $this->referenceId = $referenceId;
        $this->invoice = $invoice;
        $this->gatewayName = $gatewayName;
        $this->gatewayConfigKey = $gatewayConfigKey;
        $this->datetime = now();
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function getDate(): Carbon
    {
        return $this->datetime;
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function getGatewayConfigKey(): string
    {
        return $this->gatewayConfigKey;
    }

    public function getTransactionId(): string
    {
        return $this->invoice->getTransactionId();
    }

    public function getUuid(): string
    {
        return $this->invoice->getUuid();
    }
}
