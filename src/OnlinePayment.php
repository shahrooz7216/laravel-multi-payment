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

    public function __construct(Invoice $invoice, ?string $driverName, ?string $appName)
    {
        $this->setInvoice($invoice);
        $this->setDriverName($driverName ?? config('online-payment.default_driver'));
        $this->setAppName($appName ?? config('online-payment.default_app'));
        $this->setDriver();
        $this->setSettings();
    }

    // public function purchase(Invoice $invoice = null, $finalizeCallback = null)
    // {
    //     if ($invoice) { // create new invoice
    //         $this->invoice($invoice);
    //     }

    //     $this->driverInstance = $this->getFreshDriverInstance();

    //     //purchase the invoice
    //     $transactionId = $this->driverInstance->purchase();
    //     if ($finalizeCallback) {
    //         call_user_func_array($finalizeCallback, [$this->driverInstance, $transactionId]);
    //     }

    //     // dispatch event
    //     $this->dispatchEvent(
    //         'purchase',
    //         $this->driverInstance,
    //         $this->driverInstance->getInvoice()
    //     );

    //     return $this;
    // }

    // public function pay($initializeCallback = null)
    // {
    //     $this->driverInstance = $this->getDriverInstance();

    //     if ($initializeCallback) {
    //         call_user_func($initializeCallback, $this->driverInstance);
    //     }

    //     $this->validateInvoice();

    //     // dispatch event
    //     $this->dispatchEvent(
    //         'pay',
    //         $this->driverInstance,
    //         $this->driverInstance->getInvoice()
    //     );

    //     return $this->driverInstance->pay();
    // }

    // public function verify($finalizeCallback = null): ReceiptInterface
    // {
    //     $this->driverInstance = $this->getDriverInstance();
    //     $this->validateInvoice();
    //     $receipt = $this->driverInstance->verify();

    //     if (!empty($finalizeCallback)) {
    //         call_user_func($finalizeCallback, $receipt, $this->driverInstance);
    //     }

    //     // dispatch event
    //     $this->dispatchEvent(
    //         'verify',
    //         $receipt,
    //         $this->driverInstance,
    //         $this->driverInstance->getInvoice()
    //     );

    //     return $receipt;
    // }

    protected function getDriver()
    {
        return $this->driver;
    }

    protected function setDriver()
    {
        $this->validateDriver();
        $class = config($this->getDriverNamespaceConfigKey());
        $this->driver = new $class();
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
            throw new DriverNotFoundException('Driver not found in config file. Try updating the package.');
        }
        if (!class_exists(config($this->getDriverNamespaceConfigKey()))) {
            throw new DriverNotFoundException('Driver source not found. Please update the package.');
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

    protected function setSettings()
    {
        $this->settings = config($this->getSettingsConfigKey());
    }

    public function setDriverName(string $driverName)
    {
        $this->driverName = $driverName;
        return $this;
    }

    public function getDriverName()
    {
        return $this->driverName;
    }

    public function setAppName(string $appName)
    {
        $this->appName = $appName;
        return $this;
    }

    public function getAppName()
    {
        return $this->appName;
    }
}
