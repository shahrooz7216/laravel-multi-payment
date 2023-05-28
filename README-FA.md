<div dir="rtl">

# پکیج پرداخت آنلاین (اتصال به درگاه بانکی) در لاراول

این یک پکیج لاراول برای استفاده از درگاه های پرداخت آنلاین است که از درگاه های مختلف (بصورت درایور) با امکان تنظیم چند
حساب برای یک نوع درگاه پشتیبانی می کند. اگه درگاه موردنظرتون پشتیبانی نمیشه، میتونید خودتون از کلاس ها و قراردادهای مربوطه استفاده کنید و بنویسید. بعدش اگه خواستید Pull Request بزنید تا به پکیج اصلی اضافه بشه. خوشحال میشم در مسیر تکمیل پکیج کمک کنید.
    

</div>

> [English documents][readme-link-en]

<div dir="rtl">

## حداقل نیازمندی ها

- **PHP v7.4**
- **Laravel v7.0**

## درگاه های پشتیبانی شده

- [بانک ملت (به پرداخت)](https://behpardakht.com)
- [بانک سامان (سپ)](https://sep.ir)
- [بانک پارسیان (تاپ)](https://pec.ir)
- [بانک پاسارگاد (پپ)](https://pep.co.ir)
- [بانک اقتصاد نوین (پرداخت نوین آرین)](https://pna.co.ir)
- [زرین پال](https://zarinpal.com)
- [آیدی پی](https://idpay.ir)
- [زیبال](https://zibal.ir)
- [شبکه پرداخت پی](https://pay.ir)

## نصب

نصب از طریق composer:

</div>

```bash 
  composer require shahrooz7216/laravel-multi-payment
```

<div dir="rtl">
انتشار فایل تنظیمات اصلی با دستور زیر:
</div>

```bash
  php artisan vendor:publish --tag=multipayment-config
```

<div dir="rtl">
انتشار فایل تنظیمات درگاه مورد نظر با استفاده از تگ هر درگاه، مانند:
</div>

- zarinpal-config
- mellat-config
- saman-config

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
  php artisan vendor:publish --provider=shahrooz7216\MultiPayment\Providers\MultiPaymentServiceProvider
```

<p dir="rtl">همچنین ممکن است نیاز باشد خط زیر را به آرایه providers که در فایل app.php که در دایرکتوری config قرار دارد اضافه کنید:
</p>

```  
'providers' => [
        shahrooz7216\MultiPayment\Providers\MultiPaymentServiceProvider::class,
    ],
```

<p dir="rtl">
همچنین شما باید تنظیمات مربوط به درگاه مورد نظر(در این مورد درگاه اقتصاد نوین) را که از بخش فنی شرکت نوین پرداخت دریافت کرده اید در فایل .env سایت خود قرار دهید:
</p>

```
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
```


<div dir="rtl">
    
## تنظیمات
    
در تنظیمات اصلی (فایل multipayment.php)، می توانید در قسمت default_config درگاه پیش فرض را انتخاب کنید. مثلا مقدار zarinpal.second نشان دهنده استفاده از درگاه زرین پال با اطلاعات ورودی و تنظیمات second هست. قسمتی هم برای تنظیم واحد پولی درنظر گرفته شده که هنگام اتصال به درگاه تبدیل به ریال یا تومان بطور خودکار انجام شود.
</div>

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

<div dir="rtl">
در فایل تنظیمات مربوط به هر درگاه، مسیر کلاس درایور مربوطه، مشخصات درگاه و تنظیمات SOAP قابل تغییر هستند.
</div>

```php
    /**
     *  driver class namespace
     */
    'driver' => shahrooz7216\MultiPayment\Drivers\Zarinpal\Zarinpal::class,

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
    use shahrooz7216\MultiPayment\Facades\PaymentGateway;
    
    ////
    
    $invoice = new Invoice(10000);
    $invoice->setPhoneNumber("989123456789");
    
    return PaymentGateway::purchase($invoice, function (string $transactionId) {
        // Save transaction_id and do stuff...
    })->view();
```

<div dir="rtl">

با افزودن شماره همراه کاربر به صورتحساب، درگاه برای تجربه کاربری بهتر، شماره کارت های ثبت شده با آن را هنگام پرداخت به پرداخت کننده پیشنهاد می دهد. قبل از صدا زدن متد purchase برای خرید، می توان با استفاده از متد setProvider درگاه مورد استفاده را تغییر داد.

</div>

```php
    $invoice = new Invoice(10000);

    return PaymentGateway::setProvider('mellat', 'first')
            ->purchase($invoice, function (string $transactionId) {
                // Save transaction_id and do stuff...
            })->view();
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

- `getInvoiceId`: شناسه صورتحساب/فاکتور
- `getTransactionId`: شناسه تراکنش
- `getTraceNumber`: شماره پیگیری
- `getReferenceId`: شماره ارجاع بانکی
- `getCardNo`: شماره کارت پرداخت کننده

### سایر امکانات

#### آخرین پرداخت های موفق تایید نشده

با استفاده از متد `unverifiedPayments` در PaymentGateway می توانید لیست آخرین پرداخت هایی که موفقیت آمیز بودند اما هنوز از سمت پروژه شما verify یا تایید نشده را مشاهده کنید. فعلا فقط درگاه زرین پال از این قابلیت پشتیبانی می کند.

#### بازگشت وجه

با ارسال صورتحساب به متد `refund` می توانید پرداخت های تایید شده را به حساب پرداخت کننده برگشت بزنید. قبل از استفاده از این متد در درگاه زرین پال حتما مجوز دسترسی را در تنظیمات درگاه قرار دهید.

</div>

[readme-link-fa]: README-FA.md

[readme-link-en]: README.md
