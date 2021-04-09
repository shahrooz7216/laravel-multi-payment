<?php

namespace Omalizadeh\MultiPayment\Drivers;

use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Omalizadeh\MultiPayment\RedirectionForm;
use SoapClient;

class Irankish extends Driver
{
    public function purchase(): string
    {
        $soapOptions = $this->settings['soap_options'] ?? null;
        $data = $this->getPurchaseData();
        $soap = new SoapClient($this->getPurchaseUrl(), $soapOptions);
        $response = $soap->MakeToken($data);
        if ($response->MakeTokenResult->result == false) {
            $message = $response->MakeTokenResult->message ?? 'خطا در هنگام درخواست پرداخت';
            throw new PurchaseFailedException($message);
        }
        $transactionId = $response->MakeTokenResult->token;
        $this->invoice->setTransactionId($transactionId);

        return $transactionId;
    }

    public function pay(): RedirectionForm
    {
        $paymentUrl = $this->getPaymentUrl();
        $data = [
            'Token' => $this->invoice->getTransactionId(),
            'MerchantId' => $this->settings['merchant_id']
        ];

        return $this->redirectWithForm($paymentUrl, $data);
    }

    public function verify(): string
    {
        if (!is_null(request('ResultCode')) and request('ResultCode') != $this->getSuccessResponseStatusCode()) {
            $message = $this->getStatusMessage((int) request('ResultCode'));
            throw new PaymentFailedException($message, $status);
        }
        $soapOptions = $this->settings['soap_options'] ?? null;
        $data = $this->getVerificationData();
        $soap = new SoapClient($this->getVerificationUrl(), $soapOptions);

        $response = $soap->KicccPaymentsVerification($data);

        $status = $response->KicccPaymentsVerificationResult;

        if ($status != $this->invoice->getAmount()) {
            $message = $this->getStatusMessage((int) $status);
            throw new PaymentFailedException($message, $status);
        }

        return $data['ReferenceId'];
    }

    protected function getPurchaseData(): array
    {
        if (empty($this->settings['merchant_id'])) {
            throw new InvalidConfigurationException('Merchant id has not been set.');
        }
        if (!empty($this->invoice->getDescription())) {
            $description = $this->invoice->getDescription();
        } else {
            $description = $this->settings['description'];
        }

        return [
            'Amount' => $this->invoice->getAmount(),
            'MerchantId' => $this->settings['merchant_id'],
            'Description' => $description,
            'RevertURL' => $this->settings['callback_url'],
            'InvoiceNumber' => crc32($this->invoice->getUuid()),
            'PaymentId' => crc32($this->invoice->getUuid()),
            'SpecialPaymentId' => crc32($this->invoice->getUuid()),
        ];
    }

    protected function getVerificationData(): array
    {
        $referenceId = request('ReferenceId');

        return [
            'MerchantId' => $this->settings['merchant_id'],
            'Token' => $this->invoice->getTransactionId(),
            'ReferenceId' => $referenceId,
        ];
    }

    protected function getSuccessResponseStatusCode()
    {
        return 100;
    }

    protected function getStatusMessage($status): string
    {
        $translations = [
            110 => 'دارنده کارت انصراف داده است',
            120 => 'موجودی حساب کافی نمی باشد',
            121 => 'مبلغ تراکنشهای کارت بیش از حد مجاز است',
            130 => 'اطلاعات کارت نادرست می باشد',
            131 => 'رمز کارت اشتباه است',
            132 => 'کارت مسدود است',
            133 => 'کارت منقضی شده است',
            140 => 'زمان مورد نظر به پایان رسیده است',
            150 => 'خطای داخلی بانک به وجود آمده است',
            160 => 'خطای انقضای کارت به وجود امده یا اطلاعات CVV2 اشتباه است',
            166 => 'بانک صادر کننده کارت شما مجوز انجام تراکنش را صادر نکرده است',
            167 => 'خطا در مبلغ تراکنش',
            200 => 'مبلغ تراکنش بیش از حدنصاب مجاز',
            201 => 'مبلغ تراکنش بیش از حدنصاب مجاز برای روز کاری',
            202 => 'مبلغ تراکنش بیش از حدنصاب مجاز برای ماه کاری',
            203 => 'تعداد تراکنشهای مجاز از حد نصاب گذشته است',
            499 => 'خطای سیستمی ، لطفا مجددا تالش فرمایید',
            500 => 'خطا در تایید تراکنش های خرد شده',
            501 => 'خطا در تایید تراکتش ، ویزگی تایید خودکار',
            502 => 'آدرس آی پی نا معتبر',
            503 => 'پذیرنده در حالت تستی می باشد ، مبلغ نمی تواند بیش از حد مجاز تایین شده برای پذیرنده تستی باشد',
            504 => 'خطا در بررسی الگوریتم شناسه پرداخت',
            505 => 'مدت زمان الزم برای انجام تراکنش تاییدیه به پایان رسیده است',
            506 => 'ذیرنده یافت نشد',
            507 => 'توکن نامعتبر/طول عمر توکن منقضی شده است',
            508 => 'توکن مورد نظر یافت نشد و یا منقضی شده است',
            509 => 'خطا در پارامترهای اجباری خرید تسهیم شده',
            510 => 'خطا در تعداد تسهیم | مبالغ کل تسهیم مغایر با مبلغ کل ارائه شده | خطای شماره ردیف تکراری',
            511 => 'حساب مسدود است',
            512 => 'حساب تعریف نشده است',
            513 => 'شماره تراکنش تکراری است',
            -20 => 'در درخواست کارکتر های غیر مجاز وجو دارد',
            -30 => 'تراکنش قبلا برگشت خورده است',
            -50 => 'طول رشته درخواست غیر مجاز است',
            -51 => 'در در خواست خطا وجود دارد',
            -80 => 'تراکنش مورد نظر یافت نشد',
            -81 => ' خطای داخلی بانک',
            -90 => 'تراکنش قبلا تایید شده است'
        ];
        $unknownError = 'خطای ناشناخته رخ داده است.';

        return array_key_exists($status, $translations) ? $translations[$status] : $unknownError;
    }

    protected function getPurchaseUrl(): string
    {
        return 'https://ikc.shaparak.ir/TToken/Tokens.svc';
    }

    protected function getPaymentUrl(): string
    {
        return 'https://ikc.shaparak.ir/TPayment/Payment/index';
    }

    protected function getVerificationUrl(): string
    {
        return 'https://ikc.shaparak.ir/TVerify/Verify.svc';
    }
}
