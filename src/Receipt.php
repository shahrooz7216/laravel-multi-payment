<?php

namespace Omalizadeh\MultiPayment;

class Receipt
{
    protected Invoice $invoice;
    protected string $gatewayName;
    protected string $traceNumber;
    protected string $gatewayConfigKey;

    public function __construct($traceNumber, Invoice $invoice, string $gatewayName, string $gatewayConfigKey)
    {
        $this->traceNumber = $traceNumber;
        $this->invoice = $invoice;
        $this->gatewayName = $gatewayName;
        $this->gatewayConfigKey = $gatewayConfigKey;
    }

    public function getInvoiceId(): ?string
    {
        return $this->invoice->getInvoiceId();
    }

    public function getTraceNumber(): string
    {
        return $this->traceNumber;
    }

    public function getTransactionId(): string
    {
        return $this->invoice->getTransactionId();
    }

    public function getReferenceId(): ?string
    {
        return $this->invoice->getReferenceId();
    }

    public function getCardNo(): ?string
    {
        return $this->invoice->getCardNo();
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function getGatewayConfigKey(): string
    {
        return $this->gatewayConfigKey;
    }
}
