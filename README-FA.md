<div dir="rtl">

[![Latest Stable Version](https://poser.pugx.org/omalizadeh/laravel-multi-payment/v)](//packagist.org/packages/omalizadeh/laravel-multi-payment)
[![Tests](https://github.com/omalizadeh/laravel-multi-payment/actions/workflows/tests.yml/badge.svg)](https://github.com/omalizadeh/laravel-multi-payment/actions/workflows/tests.yml)
[![Total Downloads](https://poser.pugx.org/omalizadeh/laravel-multi-payment/downloads)](//packagist.org/packages/omalizadeh/laravel-multi-payment)
[![License](https://poser.pugx.org/omalizadeh/laravel-multi-payment/license)](//packagist.org/packages/omalizadeh/laravel-multi-payment)

# پکیج اتصال به درگاه های پرداخت در لاراول

این یک پکیج لاراول برای استفاده از درگاه های پرداخت آنلاین است که از درگاه های مختلف (بصورت درایور) با امکان تنظیم چند
حساب برای یک نوع درگاه پشتیبانی می کند. اگه درگاه موردنظرتون پشتیبانی نمیشه، میتونید خودتون از کلاس ها و قراردادهای مربوطه استفاده کنید و بنویسید و به راحتی استفاده
کنید و اگه خواستید Pull Request بزنید و تا به پکیج اضافه بشه. خوشحال میشم در این مسیر کمک کنید.

</div>

> [English documents][readme-link-en]

<div dir="rtl">

## حداقل نیازمندی ها

- **PHP v7.4**
- **Laravel v7.0**

## درگاه های پشتیبانی شده

- [ملت (به پرداخت)](https://behpardakht.com)
- [سامان (سپ)](https://sep.ir)
- [پاسارگاد (پپ)](https://pep.co.ir)
- [اقتصاد نوین (پرداخت نوین آرین)](https://pna.co.ir/)
- [زرین پال](https://zarinpal.com)

## نصب و انجام تنظیمات

نصب از طریق composer:

</div>

```bash 
  composer require omalizadeh/laravel-multi-payment
```

<div dir="rtl">
انتشار فایل تنظیمات اصلی با دستور زیر:
</div>

```bash
  php artisan vendor:publish --tag=multipayment-config
```

<div dir="rtl">
انتشار فایل تنظیمات درگاه مورد نظر با استفاده از تگ هر درگاه
</div>

- zarinpal-config
- mellat-config
- saman-config
- pasargad-config
- novin-config

<div dir="rtl">
به عنوان مثال از دستور زیر می توان برای انتشار فایل تنظیمات درگاه زرین پال استفاده کرد:
</div>

```bash
  php artisan vendor:publish --tag=zarinpal-config
```

<div dir="rtl">
در تنظیمات اصلی، می توانید در قسمت default_config درگاه پیش فرض را انتخاب کنید. مثلا مقدار zarinpal.second نشان دهنده استفاده از درگاه زرین پال با اطلاعات ورودی و تنظیمات second هست. قسمتی هم برای تنظیم واحد پولی درنظر گرفته شده که هنگام اتصال به درگاه تبدیل به ریال یا تومان بطور خودکار انجام شود.
</div>

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

<div dir="rtl">
در فایل تنظیمات مربوط به هر درگاه، مسیر کلاس درایور مربوطه، مشخصات درگاه و هدر درخواست ها یا تنظیمات مربوط به SOAP قابل تغییر هستند.
</div>

```php
    /**
     *  driver class namespace
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Zarinpal\Zarinpal::class,

    /**
     *  soap client options
     */
    'soap_options' => [
        'encoding' => 'UTF-8'
    ],

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

<div dir="rtl">

## نحوه استفاده

پرداخت با درگاه از سه بخش اصلی تشکیل می شود. مرحله اول درخواست پرداخت (Purchase)، مرحله دوم ارجاع به درگاه (Pay) و آخرین
مرحله هم تایید پرداخت (Verification) هست.

### پرداخت و ارجاع به درگاه

تمامی اطلاعات مربوط به پرداخت در صورتحساب (کلاس Invoice) ذخیره خواهند شد. برای شروع پرداخت، ابتدا یک شی از کلاس صورتحساب
ساخته و سپس اطلاعات مربوط به پرداخت مانند مبلغ را در آن ذخیره می شود. در نهایت با استفاده از کلاس پرداخت درگاه (
GatewayPayment) و متدهای مربوطه، پرداخت صورتحساب انجام می شود. هنگام ارجاع می توان درگاه موردنظر را نیز انتخاب کرد، در غیر اینصورت از تنظیمات پیش فرض استفاده می شود.

</div>

```php
    $invoice = new Invoice(10000);
    $invoice->setPhoneNumber("989123456789");
    // You can change gateway by sending gateway name as the second argument
    $gatewayPayment = new GatewayPayment($invoice, 'zarinpal.first');
    return $gatewayPayment->purchase(function ($transactionId) {
        // Save transaction_id and do stuff...
    })->pay()->render();
```

<div dir="rtl">

با افزودن شماره همراه کاربر به صورتحساب، درگاه برای تجربه کاربری بهتر، شماره کارت های ثبت شده با آن را هنگام پرداخت به پرداخت کننده پیشنهاد می دهد.

### تایید پرداخت

بعد از بازگشت کاربر از درگاه پرداخت، صورتحسابی با شماره تراکنش موردنظر تشکیل داده و با استفاده از کلاس درگاه پرداخت
موفقیت آمیز بودن آن را بررسی می کنید.

</div>

```php
    try {
        // Get amount & transaction_id from database or gateway request
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

<div dir="rtl">

خروجی تایید پرداخت، یک شیء از کلاس `Receipt` است که می توان از متدهای مختلف آن برای بدست آوردن اطلاعات مختلف استفاده
کرد.

- `getInvoiceId`: شماره صورتحساب
- `getTraceNumber`: شماره پیگیری
- `getTransactionId`: کد تراکنش
- `getReferenceId`: شماره ارجاع بانکی
- `getCardNo`: شماره کارت پرداخت کننده
- `getGatewayName`: نام درگاه
- `getGatewayConfigKey`: کلید مشخصات درگاه در تنظیمات

## تشکر ویژه

- [Shetab Multipay](https://github.com/shetabit/multipay)
- [Shetab Payment](https://github.com/shetabit/payment)

</div>

[readme-link-fa]: README-FA.md

[readme-link-en]: README.md
