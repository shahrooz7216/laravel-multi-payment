<?php

namespace Omalizadeh\MultiPayment\Drivers;

use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Omalizadeh\MultiPayment\RedirectionForm;
use Illuminate\Support\Facades\Http;

class Saman extends Driver
{
    public function purchase(): string
    {
        $data = $this->getPurchaseData();
        $response = Http::post($this->getPurchaseUrl(), $data);
        if ($response->successful()) {
            if ($response['status'] != $this->getSuccessResponseStatusCode()) {
                throw new PurchaseFailedException($response['errorDesc'], $response['errorCode']);
            } else {
                $token = $response['token'];
                $this->invoice->setToken($token);
            }
        } else {
            throw new HttpResponseException($response->body(), $response->status());
        }

        return $this->invoice->getPaymentId();
    }

    public function pay(): RedirectionForm
    {
        $payUrl = $this->getPaymentUrl();
        $data = [
            'token' => $this->invoice->getToken()
        ];

        return $this->redirectWithForm($payUrl, $data);
    }

    public function verify(): string
    {
        $data = $this->getVerificationData();
        $response = Http::post($this->getVerificationUrl(), $data);
        if ($response->successful()) {
            $responseCode = (int) $response->body();
            if ($responseCode < 0) {
                throw new PaymentFailedException($this->getStatusMessage($responseCode), $responseCode);
            } else {
                $this->invoice->setTransactionId(request('RefNum') ?? $this->invoice->getTransactionId());
                return request('TraceNo');
            }
        } else {
            throw new HttpResponseException($response->body(), $response->status());
        }
    }

    protected function getPurchaseData(): array
    {
        if (empty($this->settings['terminal_id'])) {
            throw new InvalidConfigurationException('Terminal id has not been set.');
        }
        $mobile = $this->invoice->getPhoneNumber();

        return [
            'Action' => 'Token',
            'TerminalId' => $this->settings['terminal_id'],
            'Amount' => $this->invoice->getAmount(),
            'RedirectUrl' => $this->settings['callback_url'],
            'CellNumber' => $mobile,
            'ResNum' => $this->invoice->getPaymentId(),
        ];
    }

    protected function getVerificationData(): array
    {
        return [
            'RefNum' => request('RefNum') ?? $this->invoice->getTransactionId(),
            'MerchantID' => $this->settings['terminal_id']
        ];
    }

    protected function getStatusMessage($status): string
    {
        $messages = [
            -1 => 'خطا در پردازش اطلاعات ارسالی (مشکل در یکی از ورودی ها و ناموفق بودن فراخوانی متد برگشت تراکنش)',
            -3 => 'ورودیها حاوی کاراکترهای غیرمجاز میباشند.',
            -4 => 'کلمه عبور یا کد فروشنده اشتباه است (Merchant Authentication Failed)',
            -6 => 'سند قبال برگشت کامل یافته است. یا خارج از زمان 30 دقیقه ارسال شده است.',
            -7 => 'رسید دیجیتالی تهی است.',
            -8 => 'طول ورودیها بیشتر از حد مجاز است.',
            -9 => 'وجود کاراکترهای غیرمجاز در مبلغ برگشتی.',
            -10 => 'رسید دیجیتالی به صورت Base64 نیست (حاوی کاراکترهای غیرمجاز است)',
            -11 => 'طول ورودیها کمتر از حد مجاز است.',
            -12 => 'مبلغ برگشتی منفی است.',
            -13 => 'مبلغ برگشتی برای برگشت جزئی بیش از مبلغ برگشت نخورده ی رسید دیجیتالی است.',
            -14 => 'چنین تراکنشی تعریف نشده است.',
            -15 => 'مبلغ برگشتی به صورت اعشاری داده شده است.',
            -16 => 'خطای داخلی سیستم',
            -17 => 'برگشت زدن جزیی تراکنش مجاز نمی باشد.',
            -18 => 'IP Address فروشنده نا معتبر است و یا رمز تابع بازگشتی (reverseTransaction) اشتباه است.',
            1 => 'کاربر انصراف داده است.',
            2 => 'پرداخت با موفقیت انجام شد.',
            3 => 'پرداخت انجام نشد.',
            4 => 'کاربر در بازه زمانی تعیین شده پاسخی ارسال نکرده است.',
            5 => 'پارامترهای ارسالی نامعتبر است.',
            8 => 'آدرس سرور پذیرنده نامعتبر است.',
            10 => 'توکن ارسال شده یافت نشد.',
            11 => 'با این شماره ترمینال فقط تراکنش های توکنی قابل پرداخت هستند.',
            12 => 'شماره ترمینال ارسال شده یافت نشد.',
        ];
        $unknownError = 'خطای ناشناخته رخ داده است.';

        return array_key_exists($status, $messages) ? $messages[$status] : $unknownError;
    }

    protected function getSuccessResponseStatusCode(): int
    {
        return 1;
    }

    protected function getPurchaseUrl(): string
    {
        return 'https://sep.shaparak.ir/MobilePG/MobilePayment';
    }

    protected function getPaymentUrl(): string
    {
        return 'https://sep.shaparak.ir/OnlinePG/OnlinePG';
    }

    protected function getVerificationUrl(): string
    {
        return 'https://verify.sep.ir/Payments/ReferencePayment.asmx';
    }
}
