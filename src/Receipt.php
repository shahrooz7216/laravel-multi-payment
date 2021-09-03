<?php

namespace Omalizadeh\MultiPayment;

class Receipt
{
    protected Invoice $invoice;
    protected string $traceNumber;
    protected ?string $cardNumber;
    protected ?string $referenceId;

    public function __construct(
        Invoice $invoice,
        string $traceNumber,
        ?string $referenceId = null,
        ?string $cardNumber = null
    ) {
        $this->invoice = $invoice;
        $this->traceNumber = $traceNumber;
        $this->referenceId = $referenceId;
        $this->cardNumber = $cardNumber;
    }

    public function getInvoiceId(): string
    {
        return $this->invoice->getInvoiceId();
    }

    public function getTransactionId(): string
    {
        return $this->invoice->getTransactionId();
    }

    public function getTraceNumber(): string
    {
        return $this->traceNumber;
    }

    public function getReferenceId(): ?string
    {
        return $this->referenceId;
    }

    public function getCardNumber(): ?string
    {
        return $this->cardNumber;
    }
}
