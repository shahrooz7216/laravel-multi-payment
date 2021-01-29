<?php

namespace Omalizadeh\MultiPayment\Drivers;

use Omalizadeh\MultiPayment\Invoice;
use Omalizadeh\MultiPayment\RedirectionForm;

abstract class Driver implements DriverInterface
{
    protected Invoice $invoice;
    protected array $settings;

    public function __construct(Invoice $invoice, array $settings)
    {
        $this->invoice = $invoice;
        $this->settings = $settings;
    }

    abstract public function purchase(): string;

    abstract public function pay(): RedirectionForm;

    abstract public function verify(): string;

    abstract protected function getStatusMessage($statusCode): string;

    abstract protected function getPurchaseUrl(): string;

    abstract protected function getPaymentUrl(): string;

    abstract protected function getVerificationUrl(): string;

    abstract protected function getResponseSuccessStatusCode(): string;

    public function redirectWithForm($action, array $inputs = [], $method = 'POST'): RedirectionForm
    {
        return new RedirectionForm($action, $inputs, $method);
    }
}
