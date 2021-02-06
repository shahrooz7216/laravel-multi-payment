<?php

namespace Omalizadeh\MultiPayment;

use Exception;
use Omalizadeh\MultiPayment\Drivers\DriverInterface;
use Omalizadeh\MultiPayment\Exceptions\AppNotFoundException;
use Omalizadeh\MultiPayment\Exceptions\DriverNotFoundException;
use ReflectionClass;

class OnlinePayment
{
    protected array $settings;
    protected string $driverName;
    protected string $appName;
    protected Invoice $invoice;
    protected DriverInterface $driver;

    public function __construct(Invoice $invoice, ?string $driverName = null, ?string $appName = null)
    {
        $this->setInvoice($invoice);
        $this->setDriverName($driverName ?? config('online-payment.default_driver'));
        $this->setAppName($appName ?? config('online-payment.default_app'));
        $this->setSettings();
        $this->setDriver();
    }

    public function purchase(): string
    {
        return $this->getDriver()->purchase();
    }

    public function pay(): RedirectionForm
    {
        return $this->getDriver()->pay();
    }

    public function verify(): Receipt
    {
        $refId = $this->getDriver()->verify();

        return new Receipt($refId, $this->getInvoice(), $this->getDriverName(), $this->getAppName());
    }

    protected function validateDriver()
    {
        if (empty($this->getDriverName())) {
            throw new DriverNotFoundException('Driver not selected or default driver does not exist.');
        }
        if (empty($this->getAppName())) {
            throw new AppNotFoundException('App not selected or default app does not exist.');
        }
        if (empty(config($this->getSettingsConfigKey())) or empty(config($this->getDriverNamespaceConfigKey()))) {
            throw new DriverNotFoundException('Driver settings not found in config file.');
        }
        if (!class_exists(config($this->getDriverNamespaceConfigKey()))) {
            throw new DriverNotFoundException('Driver class not found. Check driver aliases or try updating the package');
        }
        $reflect = new ReflectionClass(config($this->getDriverNamespaceConfigKey()));
        if (!$reflect->implementsInterface(DriverInterface::class)) {
            throw new Exception("Driver must implement DriverInterface.");
        }
    }

    protected function getSettingsConfigKey()
    {
        return 'online-payment.' . $this->getAppName() . '.' . $this->getDriverName();
    }

    protected function getDriverNamespaceConfigKey()
    {
        return 'online-payment.aliases.' . $this->getDriverName();
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

    public function setDriverName(string $driverName)
    {
        $this->driverName = $driverName;
        return $this;
    }

    public function setAppName(string $appName)
    {
        $this->appName = $appName;
        return $this;
    }

    public function getDriverName(): string
    {
        return $this->driverName;
    }

    public function getAppName(): string
    {
        return $this->appName;
    }

    protected function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    protected function getInvoice(): Invoice
    {
        return $this->invoice;
    }
}
