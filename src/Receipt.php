<?php

namespace Omalizadeh\MultiPayment;

use Carbon\Carbon;

class Receipt
{
    protected string $appName;
    protected Carbon $datetime;
    protected Invoice $invoice;
    protected string $driverName;
    protected string $referenceId;

    public function __construct($referenceId, Invoice $invoice, string $driverName, string $appName)
    {
        $this->referenceId = $referenceId;
        $this->invoice = $invoice;
        $this->driverName = $driverName;
        $this->appName = $appName;
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

    public function getDriverName(): string
    {
        return $this->driverName;
    }

    public function getAppName(): string
    {
        return $this->appName;
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
