
# Laravel Online Payment Gateway Package

This is a laravel gateway payment package with multi driver support. Each driver can have multiple configurations.

Supports laravel **v7.0+** and requires php **v7.4+**

<div dir="rtl">

> **[مستندات فارسی][readme-link-fa]**
</div>

## Supported Gateways

- [Mellat Bank (Behpardakht)](https://behpardakht.com)
- [Saman Bank (Sep)](https://sep.ir)
- [Parsian Bank (Top)](https://pec.ir)
- [Pasargad Bank (Pep)](https://pep.co.ir)
- [Eghtesad Novin Bank (Pardakht Novin)](https://pna.co.ir)
- [Zarinpal](https://zarinpal.com)
- [IDPay](https://idpay.ir)
- [Pay.ir](https://pay.ir)
- [Zibal](https://zibal.ir)

## Installation & Configuration

Install using composer

```bash 
  composer require shahrooz7216/laravel-multi-payment
```

Publish main config file

```bash
  php artisan vendor:publish --tag=multipayment-config
```

Publish gateway config file based on tags. like:
- zarinpal-config
- mellat-config
- saman-config
- pasargad-config
- novin-config
  
For example:

```bash
  php artisan vendor:publish --tag=zarinpal-config
```
Also you may have to add Service provider to the providers Array in app.php file in config directory:

```
'providers' => [
    shahrooz7216\MultiPayment\Providers\MultiPaymentServiceProvider::class,
],
```

And you should add your configuration environment variables to your .env file:
````
    EGHTESAD_NOVIN_TERMINAL_ID="..."
    EGHTESAD_NOVIN_MERCHANT_ID="..."
    EGHTESAD_NOVIN_MID="..."
    EGHTESAD_NOVIN_USER_ID="..."
    EGHTESAD_NOVIN_PASSWORD="..."
    EGHTESAD_NOVIN_TOKEN=""
    EGHTESAD_NOVIN_CALLBACK_URL="https://something.com/novin.php"
    EGHTESAD_NOVIN_API_ENDPOINT="https://pna.shaparak.ir"
    EGHTESAD_NOVIN_CERT_PATH="..."
    EGHTESAD_NOVIN_CERT_USERNAME="..."
    EGHTESAD_NOVIN_CERT_PASSWORD="..."
    EGHTESAD_NOVIN_MODE="NoSign" 
````


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
    'default_gateway' => env('DEFAULT_PAYMENT_GATEWAY', 'zarinpal.second'),

    /**
     *  set to false if your in-app currency is IRR
     */
    'convert_to_rials' => true
```

In each gateway config file, you can specify multiple credentials, therefore you may have multiple gateways for your app from same provider.

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

Gateway payment has two major phases. first is purchase (start process by calling gateway api for a
transaction_id/token) and opening gateway payment web page with received data. second is verification (checking
payment was successful).

### Purchase

`Inovice` objects hold payment data. first you create an invoice, set amount and other information, then you pass invoice to `PaymentGateway` Facade to start payment process. you can use `setProvider` method on facade to change gateway before payment.

```php
    // On top...
    use shahrooz7216\MultiPayment\Facades\PaymentGateway;

    ////

    $invoice = new Invoice(10000);
    $invoice->setPhoneNumber("989123456789");
    
    return PaymentGateway::purchase($invoice, function (string $transactionId) {
        // Save transaction_id and do stuff...
    })->view();
```

### Verification

After payment gateway redirection to your app, you must create an invoice and set it's transaction_id and amount. then use `PaymentGateway` to verify invoice successful payment.

```php
    try {
        // Get amount & transaction_id from database or gateway request
        $invoice = new Invoice($amount, $transactionId);
        $receipt = PaymentGateway::verify($invoice);
        // Save receipt data and return response
        //
    } catch (PaymentAlreadyVerifiedException $exception) {
        // Optional: Handle repeated verification request
    } catch (PaymentFailedException $exception) {
        // Handle exception for failed payments
        return $exception->getMessage();
    }
```

### Other Features

#### Unverified Payments

There is also a method (supported by zarinpal only for now) to get a list of successful unverified payments. use `unverifiedPayments` method in `PaymentGateway` facade for this feature.

#### Refund

Using `refund` method, you can refund a successful payment back to customer.

[readme-link-fa]: README-FA.md

[readme-link-en]: README.md
