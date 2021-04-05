<?php

namespace Omalizadeh\MultiPayment;

use Exception;
use Omalizadeh\MultiPayment\Drivers\DriverInterface;
use Omalizadeh\MultiPayment\Exceptions\ConfigurationNotFoundException;
use Omalizadeh\MultiPayment\Exceptions\DriverNotFoundException;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use ReflectionClass;

class GatewayPayment
{
    protected array $settings;
    protected string $gatewayName;
    protected string $gatewayConfigKey;
    protected Invoice $invoice;
    protected DriverInterface $driver;

    public function __construct(Invoice $invoice, ?string $gateway = null)
    {
        $gatewayConfig = explode('.', $gateway ?? config('multi-payment.default_gateway'));
        if (count($gatewayConfig) !== 2 or empty($gatewayConfig[0]) or empty($gatewayConfig[1])) {
            throw new InvalidConfigurationException('invalid gateway. valid gateway pattern: GATEWAY_NAME.GATEWAY_SETTINGS_KEY');
        }
        $this->setInvoice($invoice);
        $this->setGatewayName($gatewayConfig[0]);
        $this->setGatewayConfigKey($gatewayConfig[1]);
        $this->setSettings();
        $this->setDriver();
    }

    public function purchase(?callable $callbackFunction = null): GatewayPayment
    {
        $transactionId = $this->getDriver()->purchase();
        if ($callbackFunction) {
            call_user_func($callbackFunction, $transactionId);
        }
        return $this;
    }

    public function pay(): RedirectionForm
    {
        return $this->getDriver()->pay();
    }

    public function verify(): Receipt
    {
        $refId = $this->getDriver()->verify();

        return new Receipt($refId, $this->getInvoice(), $this->getGatewayName(), $this->getGatewayConfigKey());
    }

    protected function getSettingsConfigKey()
    {
        return 'gateway-' . $this->getGatewayName() . '.' . $this->getGatewayConfigKey();
    }

    protected function getDriverNamespaceConfigKey()
    {
        return 'gateway-' . $this->getGatewayName() . '.driver';
    }

    protected function setInvoice(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    protected function setDriver()
    {
        $this->validateDriver();
        $class = config($this->getDriverNamespaceConfigKey());
        $this->driver = new $class($this->invoice, $this->settings);
    }

    protected function setSettings()
    {
        $this->settings = config($this->getSettingsConfigKey());
    }

    public function setGatewayName(string $gatewayName)
    {
        $this->gatewayName = $gatewayName;
        return $this;
    }

    public function setGatewayConfigKey(string $gatewayConfigKey)
    {
        $this->gatewayConfigKey = $gatewayConfigKey;
        return $this;
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function getGatewayConfigKey(): string
    {
        return $this->gatewayConfigKey;
    }

    protected function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    protected function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    private function validateDriver()
    {
        if (empty($this->getGatewayName())) {
            throw new ConfigurationNotFoundException('gateway not selected or default gateway does not exist.');
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
        if (!$reflect->implementsInterface(DriverInterface::class)) {
            throw new Exception("Driver must implement DriverInterface.");
        }
    }
}
