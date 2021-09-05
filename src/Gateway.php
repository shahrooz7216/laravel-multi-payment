<?php

namespace Omalizadeh\MultiPayment;

use Closure;
use Exception;
use ReflectionClass;
use Omalizadeh\MultiPayment\Exceptions\ConfigurationNotFoundException;
use Omalizadeh\MultiPayment\Exceptions\DriverNotFoundException;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Drivers\Contracts\PaymentInterface;
use Omalizadeh\MultiPayment\Drivers\Contracts\UnverifiedPaymentsInterface;

class Gateway
{
    protected array $settings;
    protected string $gatewayName;
    protected string $gatewayConfigKey;
    protected PaymentInterface $driver;

    public function purchase(Invoice $invoice, ?Closure $callbackFunction = null): RedirectionForm
    {
        $transactionId = $this->getDriver()->setInvoice($invoice)->purchase();

        if ($callbackFunction) {
            call_user_func($callbackFunction, $transactionId);
        }

        return $this->getDriver()->pay();
    }

    public function verify(Invoice $invoice): Receipt
    {
        return $this->getDriver()->setInvoice($invoice)->verify();
    }

    public function unverifiedPayments(): array
    {
        $this->validateUnverifiedInterfaceImplementation();
        return $this->getDriver()->latestUnverifiedPayments();
    }

    public function setGateway(string $gateway): Gateway
    {
        $gatewayConfig = explode('.', $gateway);
        if (count($gatewayConfig) !== 2 or empty($gatewayConfig[0]) or empty($gatewayConfig[1])) {
            throw new InvalidConfigurationException('Invalid gateway. valid gateway pattern: GATEWAY_NAME.GATEWAY_CONFIG_KEY');
        }

        $this->setGatewayName($gatewayConfig[0]);
        $this->setGatewayConfigKey($gatewayConfig[1]);
        $this->setSettings();
        $this->setDriver();

        return $this;
    }

    public function getGatewayName(): string
    {
        if (empty($this->gatewayName)) {
            $this->setDefaultGateway();
        }

        return $this->gatewayName;
    }

    public function getGatewayConfigKey(): string
    {
        if (empty($this->gatewayConfigKey)) {
            $this->setDefaultGateway();
        }

        return $this->gatewayConfigKey;
    }

    private function setDriver(): void
    {
        $this->validateDriver();
        $class = config($this->getDriverNamespaceConfigKey());
        $this->driver = new $class($this->settings);
    }

    private function setSettings(): void
    {
        $settings = config($this->getSettingsConfigKey());
        if (empty($settings) or !is_array($settings)) {
            throw new InvalidConfigurationException('Settings for ' . $this->getSettingsConfigKey() . ' not found.');
        }
        $this->settings = $settings;
    }

    private function setGatewayName(string $gatewayName): void
    {
        $this->gatewayName = $gatewayName;
    }

    private function setGatewayConfigKey(string $gatewayConfigKey): void
    {
        $this->gatewayConfigKey = $gatewayConfigKey;
    }

    private function getSettingsConfigKey(): string
    {
        return 'gateway_' . $this->getGatewayName() . '.' . $this->getGatewayConfigKey();
    }

    private function getDriverNamespaceConfigKey(): string
    {
        return 'gateway_' . $this->getGatewayName() . '.driver';
    }

    private function getDriver(): PaymentInterface
    {
        if (empty($this->driver)) {
            $this->setDefaultGateway();
        }
        return $this->driver;
    }

    private function setDefaultGateway(): Gateway
    {
        return $this->setGateway(config('multipayment.default_gateway'));
    }

    private function validateDriver(): void
    {
        if (empty($this->getGatewayName())) {
            throw new ConfigurationNotFoundException('Gateway not selected or default gateway does not exist.');
        }
        if (empty($this->getGatewayConfigKey())) {
            throw new ConfigurationNotFoundException('Gateway configuration key not selected or default configuration does not exist.');
        }
        if (empty(config($this->getSettingsConfigKey())) or empty(config($this->getDriverNamespaceConfigKey()))) {
            throw new DriverNotFoundException('Gateway driver settings not found in config file.');
        }
        if (!class_exists(config($this->getDriverNamespaceConfigKey()))) {
            throw new DriverNotFoundException('Gateway driver class not found. Check driver aliases or try updating the package');
        }

        $reflect = new ReflectionClass(config($this->getDriverNamespaceConfigKey()));
        if (!$reflect->implementsInterface(PaymentInterface::class)) {
            throw new Exception("Driver must implement PaymentInterface.");
        }
    }

    private function validateUnverifiedInterfaceImplementation()
    {
        $reflect = new ReflectionClass($this->getDriver());
        if (!$reflect->implementsInterface(UnverifiedPaymentsInterface::class)) {
            throw new Exception("Driver does not support unverified payments.");
        }
    }
}
