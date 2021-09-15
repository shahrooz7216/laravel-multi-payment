<div dir="rtl">

[![Latest Stable Version](https://poser.pugx.org/omalizadeh/laravel-multi-payment/v)](//packagist.org/packages/omalizadeh/laravel-multi-payment)
[![Tests](https://github.com/omalizadeh/laravel-multi-payment/actions/workflows/tests.yml/badge.svg)](https://github.com/omalizadeh/laravel-multi-payment/actions/workflows/tests.yml)
[![Total Downloads](https://poser.pugx.org/omalizadeh/laravel-multi-payment/downloads)](//packagist.org/packages/omalizadeh/laravel-multi-payment)
[![License](https://poser.pugx.org/omalizadeh/laravel-multi-payment/license)](//packagist.org/packages/omalizadeh/laravel-multi-payment)

# پکیج پرداخت آنلاین (اتصال به درگاه بانکی) در لاراول

این یک پکیج لاراول برای استفاده از درگاه های پرداخت آنلاین است که از درگاه های مختلف (بصورت درایور) با امکان تنظیم چند
حساب برای یک نوع درگاه پشتیبانی می کند. اگه درگاه موردنظرتون پشتیبانی نمیشه، میتونید خودتون از کلاس ها و قراردادهای مربوطه استفاده کنید و بنویسید و به راحتی استفاده
کنید و اگه خواستید Pull Request بزنید تا به پکیج اصلی اضافه بشه. خوشحال میشم در مسیر تکمیل پکیج کمک کنید.
    
> اگه از پکیج استفاده کردین، ستاره فراموش نشه!

</div>

> [English documents][readme-link-en]

<div dir="rtl">

## حداقل نیازمندی ها

- **PHP v7.4**
- **Laravel v7.0**

## درگاه های پشتیبانی شده

- [بانک ملت (به پرداخت)](https://behpardakht.com)
- [بانک سامان (سپ)](https://sep.ir)
- [بانک پاسارگاد (پپ)](https://pep.co.ir)
- [بانک اقتصاد نوین (پرداخت نوین آرین)](https://pna.co.ir/)
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
یا می توانید تمامی فایل های تنظیمات پکیج را با دستور زیر منتشر کنید:
</div>

```bash
  php artisan vendor:publish --provider=Omalizadeh\MultiPayment\Providers\MultiPaymentServiceProvider
```

<div dir="rtl">
در تنظیمات اصلی (فایل multipayment.php)، می توانید در قسمت default_config درگاه پیش فرض را انتخاب کنید. مثلا مقدار zarinpal.second نشان دهنده استفاده از درگاه زرین پال با اطلاعات ورودی و تنظیمات second هست. قسمتی هم برای تنظیم واحد پولی درنظر گرفته شده که هنگام اتصال به درگاه تبدیل به ریال یا تومان بطور خودکار انجام شود.
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
     *  rest api call headers
     */
    'request_headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
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

پرداخت با درگاه در این پکیج از دو بخش اصلی تشکیل می شود. مرحله اول درخواست پرداخت و ارجاع به درگاه (Purchase) و مرحله دوم تایید پرداخت (Verification) است.

### پرداخت و ارجاع به درگاه

تمامی اطلاعات مربوط به پرداخت در صورتحساب (کلاس Invoice) ذخیره خواهند شد. برای شروع پرداخت، ابتدا یک شی از کلاس صورتحساب
ساخته و سپس اطلاعات مربوط به پرداخت مانند مبلغ در آن ذخیره می شود. در نهایت با استفاده از کلاس درگاه پرداخت که به صورت Facade پیاده سازی شده است، (PaymentGateway) پرداخت صورتحساب انجام می شود.

</div>

```php
    use Omalizadeh\MultiPayment\Facades\PaymentGateway;
    
    ////
    
    $invoice = new Invoice(10000);
    $invoice->setPhoneNumber("989123456789");
    
    return PaymentGateway::purchase($invoice, function (string $transactionId) {
        // Save transaction_id and do stuff...
    })->render();
```

<div dir="rtl">

با افزودن شماره همراه کاربر به صورتحساب، درگاه برای تجربه کاربری بهتر، شماره کارت های ثبت شده با آن را هنگام پرداخت به پرداخت کننده پیشنهاد می دهد. قبل از صدا زدن متد purchase برای خرید، می توان با استفاده از متد setGateway درگاه مورد استفاده را تغییر داد.

</div>

```php
    $invoice = new Invoice(10000);

    return PaymentGateway::setGateway('mellat.app')
            ->purchase($invoice, function (string $transactionId) {
                // Save transaction_id and do stuff...
            })->render();
```

<div dir="rtl">

### تایید پرداخت

بعد از بازگشت کاربر از درگاه پرداخت، صورتحسابی با شماره تراکنش و مبلغ موردنظر تشکیل داده و با استفاده از متد verify در PaymentGateway
موفقیت آمیز بودن آن را بررسی می کنید. دقت کنید که اگر صورتحساب مربوط به درگاه پیش فرض نیست، قبل از صحت سنجی درگاه را با متد مربوطه تغییر دهید.

</div>

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

<div dir="rtl">

خروجی تایید پرداخت، یک شیء از کلاس `Receipt` است که می توان از متدهای مختلف آن برای بدست آوردن اطلاعات مختلف استفاده
کرد.

- `getInvoiceId`: شماره صورتحساب
- `getTransactionId`: کد تراکنش
- `getTraceNumber`: شماره پیگیری
- `getReferenceId`: شماره ارجاع بانکی
- `getCardNo`: شماره کارت پرداخت کننده

### سایر امکانات

#### آخرین پرداخت های موفق تایید نشده

با استفاده از متد `unverifiedPayments` می توانید لیست آخرین پرداخت هایی که موفقیت آمیز بودند اما هنوز از سمت پروژه شما verify یا تایید نشده را مشاهده کنید. فعلا فقط درگاه زرین پال از این قابلیت پشتیبانی می کند.

</div>

[readme-link-fa]: README.md

[readme-link-en]: README-EN.md
