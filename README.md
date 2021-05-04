# Laravel Multi Payment

This is a laravel gateway payment package with multi driver support. Each driver can have multiple configurations. Supports laravel **v7.0+** and requires php **v7.4+**

## Supported Gateways

 - [Mellat (Behpardakht)](https://behpardakht.com)
 - [Saman (Sep)](https://sep.ir)
 - [Zarinpal](https://zarinpal.com)

## Installation & Configuration

Install using composer

```bash 
  composer require omalizadeh/laravel-multi-payment
```
Publish gateway config files
```bash
  php artisan vendor:publish --provider="Omalizadeh\MultiPayment\Providers\MultiPaymentServiceProvider"
```
This command will publish main config file `multipayment.php` and gateway configs each in a seprated file. You can delete config files for gateways that you don't use. In main config file you can specify default driver. There is also an option for auto payment amount conversion from Iranian Tomans to Iranian Rials currency (IRR).
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
In each gateway config file, you can specify multiple credentials and therefore you may have multiple zarinpal gateways for your app.
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
Gateway payment has three major phases. first is purchase (start process by calling gateway api for a transaction_id/token). second is payment (opening gateway payment web page). third is payment verification (checking payment was successful).
### Purchase & Payment
`Inovice` objects hold payment data. first you create an invoice, set amount and other information, then you pass invoice to a `GatewayPayment` object to init payment process.
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
After gateway redirection to your app, you must create an invoice and set it's transaction_id then use `GatewayPayment` for invoice payment verification.
```php
      try {
          // Get amount & transaction_id from database
          $invoice = new Invoice($amount);
          $invoice->setTransactionId($transactionId);
          $gatewayPayment = new GatewayPayment($invoice, 'zarinpal.first');
          $receipt = $gatewayPayment->verify();
          $traceNo = $receipt->getReferenceId();
          // Save traceNo and return response
      } catch (PaymentFailedException $exception) {
          // Handle exception for failed payments
          return $exception->getMessage();
      }
```

## Acknowledgements

 - [Shetab Multipay](https://github.com/shetabit/multipay)
 - [Shetab Payment](https://github.com/shetabit/payment)
   