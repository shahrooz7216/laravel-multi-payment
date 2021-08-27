[![Latest Stable Version](https://poser.pugx.org/omalizadeh/laravel-multi-payment/v)](//packagist.org/packages/omalizadeh/laravel-multi-payment)
[![Tests](https://github.com/omalizadeh/laravel-multi-payment/actions/workflows/tests.yml/badge.svg)](https://github.com/omalizadeh/laravel-multi-payment/actions/workflows/tests.yml)
[![Total Downloads](https://poser.pugx.org/omalizadeh/laravel-multi-payment/downloads)](//packagist.org/packages/omalizadeh/laravel-multi-payment)
[![License](https://poser.pugx.org/omalizadeh/laravel-multi-payment/license)](//packagist.org/packages/omalizadeh/laravel-multi-payment)

# Laravel Multi Payment

This is a laravel gateway payment package with multi driver support. Each driver can have multiple configurations.
Supports laravel **v7.0+** and requires php **v7.4+**

<div dir="rtl">

> **[مستندات فارسی][readme-link-fa]**
</div>

## Supported Gateways

- [Mellat (Behpardakht)](https://behpardakht.com)
- [Saman (Sep)](https://sep.ir)
- [Pasargad (Pep)](https://pep.co.ir)
- [Eghtesad Novin (Pardakht Novin)](https://pna.co.ir)
- [Zarinpal](https://zarinpal.com)

## Installation & Configuration

Install using composer

```bash 
  composer require omalizadeh/laravel-multi-payment
```

Publish main config file

```bash
  php artisan vendor:publish --tag=multipayment-config
```

Publish gateway config file based on these tags.
- zarinpal-config
- mellat-config
- saman-config
- pasargad-config
- novin-config
  
For example:

```bash
  php artisan vendor:publish --tag=zarinpal-config
```

Also you can publish view file for gateway redirection and customize it
```bash
  php artisan vendor:publish --tag=multipayment-view
```

In main config file `multipayment.php`, you can specify default driver. For example, `zarinpal.second` value states that `zarinpal` gateway with configuration under `second` key section on zarinpal config file will be used. There is also an option for auto amount conversion from Iranian Tomans to Iranian Rials currency (IRR) and vice versa.

```php
     /**
     * set default gateway
     * 
     * valid pattern --> GATEWAY_NAME.GATEWAY_CONFIG_KEY 
     */
    'default_gateway' => env('DEFAULT_GATEWAY', 'zarinpal.second'),

    /**
     *  set to false if your in-app currency is IRR
     */
    'convert_to_rials' => true
```

In each gateway config file, you can specify multiple credentials and therefore you may have multiple gateways for your app.

```php
     /**
     *  gateway configurations
     */
    'first' => [
        'merchant_id'  => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'mode'        => 'normal', // Supported values: normal, sandbox, zaringate
        'description' => 'payment using zarinpal',
    ],
    'second' => [
        'merchant_id'  => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'mode'        => 'sandbox',
        'description' => 'payment using zarinpal',
    ]
```

## Usage

Gateway payment has three major phases. first is purchase (start process by calling gateway api for a
transaction_id/token). second is payment (opening gateway payment web page). third is payment verification (checking
payment was successful).

### Purchase & Payment

`Inovice` objects hold payment data. first you create an invoice, set amount and other information, then you pass
invoice to a `GatewayPayment` object to init payment process.

```php
      $invoice = new Invoice(10000);
      $invoice->setPhoneNumber("989123456789");
      // You can change gateway by sending gateway name as the second argument
      $gatewayPayment = new GatewayPayment($invoice, 'zarinpal.first');
      return $gatewayPayment->purchase(function ($transactionId) {
          // Save transaction_id and do stuff...
      })->pay()->render();
```

### Verification

After gateway redirection to your app, you must create an invoice and set it's transaction_id then use `GatewayPayment`
for invoice payment verification.

```php
      try {
          // Get amount & transaction_id from database
          $invoice = new Invoice($amount);
          $invoice->setTransactionId($transactionId);
          $gatewayPayment = new GatewayPayment($invoice, 'zarinpal.first');
          $receipt = $gatewayPayment->verify();
          $traceNo = $receipt->getTraceNumber();
          // Save traceNo and return response
      } catch (PaymentFailedException $exception) {
          // Handle exception for failed payments
          return $exception->getMessage();
      }
```

## Acknowledgements

- [Shetab Multipay](https://github.com/shetabit/multipay)
- [Shetab Payment](https://github.com/shetabit/payment)

[readme-link-fa]: README-FA.md

[readme-link-en]: README.md
