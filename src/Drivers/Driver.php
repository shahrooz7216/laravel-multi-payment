<?php

namespace Omalizadeh\MultiPayment\Drivers;

use Omalizadeh\MultiPayment\Invoice;
use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;

abstract class Driver implements DriverInterface
{
    protected Invoice $invoice;
    protected array $settings;

    public function __construct(Invoice $invoice, array $settings)
    {
        $this->setInvoice($invoice);
        $this->settings = $settings;
    }

    abstract public function purchase();

    abstract public function pay(): RedirectionForm;

    abstract public function verify(): Receipt;

    abstract protected function getStatusMessage($statusCode): string;

    abstract protected function getPurchaseUrl(): string;

    abstract protected function getPaymentUrl(): string;

    abstract protected function getVerificationUrl(): string;

    abstract protected function getSuccessStatusCode(): string;

    // public function redirectWithForm($action, array $inputs = [], $method = 'POST'): RedirectionForm
    // {
    //     return new RedirectionForm($action, $inputs, $method);
    // }

    protected function setInvoice($invoice)
    {
        $this->invoice = $invoice;
        return $this;
    }

    protected function getInvoice()
    {
        return $this->invoice;
    }
}
