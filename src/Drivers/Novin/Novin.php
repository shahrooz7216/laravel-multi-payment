<?php

namespace Omalizadeh\MultiPayment\Drivers\Novin;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Omalizadeh\MultiPayment\Exceptions\HttpRequestFailedException;
use Omalizadeh\MultiPayment\RedirectionForm;
use Omalizadeh\MultiPayment\Drivers\Contracts\Driver;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;

class Novin extends Driver
{
    const BANK_BUY_TRANSACTION_TYPE = 'EN_GOODS';

    public function purchase(): string
    {
        $sessionId = cache($this->getSessionIdCacheKey());
        if (empty($sessionId)) {
            $response = $this->callApi($this->getLoginUrl(), $this->getLoginData());
            if ($response['Result'] == $this->getSuccessResponseStatusCode()) {
                cache([
                    $this->getSessionIdCacheKey() => $response['SessionId']
                ]);
            } else {
                throw new PurchaseFailedException($this->getStatusMessage($response['Result']));
            }
        }
        $purchaseData = $this->getPurchaseData();
        $response = $this->callApi($this->getPurchaseUrl(), $purchaseData);
        if ($response['Result'] == $this->getSuccessResponseStatusCode()) {
            $dataToSign = $response['DataToSign'];
            $dataUniqueId = $response['UniqueId'];
            $signature = $this->getSignature($dataToSign);
            $tokenGenerationData = [
                'WSContext' => $this->getAuthData(),
                'Signature' => $signature,
                'UniqueId' => $dataUniqueId
            ];
            $response = $this->callApi($this->getTokenGenerationUrl(), $tokenGenerationData);
            if ($response['Result'] == $this->getSuccessResponseStatusCode()) {
                $token = $response['Token'];
                $this->invoice->setToken($token);
                return $this->invoice->getInvoiceId();
            }
            throw new PurchaseFailedException($this->getStatusMessage($response['Result']));
        }
        throw new PurchaseFailedException($this->getStatusMessage($response['Result']));

    }

    public function pay(): RedirectionForm
    {
        $payUrl = $this->getPaymentUrl();
        $data = [
            'Token' => $this->invoice->getToken(),
            'Language' => $this->getLanguage()
        ];
        $payUrl .= ('?token=' . $data['Token'] . '&language=' . $data['Language']);

        return $this->redirectWithForm($payUrl, $data);
    }

    public function verify(): string
    {
        if (request('State') and strtoupper(request('State')) != 'OK') {
            throw new PaymentFailedException('کاربر از انجام تراکنش منصرف شده است.');
        }
        $verificationData = $this->getVerificationData();
        $response = $this->callApi($this->getVerificationUrl(), $verificationData);
        if ($response['Result'] == $this->getSuccessResponseStatusCode() and $response['Amount'] == $this->invoice->getAmount()) {
            $this->invoice->setTransactionId(request('RefNum'));
            $this->invoice->setReferenceId(request('CustomerRefNum'));
            $this->invoice->setInvoiceId(request('ResNum'));
            $this->invoice->setCardNo(request('CardMaskPan'));
            return request('TraceNo');
        }
        throw new PaymentFailedException($this->getStatusMessage($response['Result']));
    }

    private function getSignature(string $dataToSign): string
    {
        $unsignedFile = fopen($this->getUnsignedDataFilePath(), "w");
        fwrite($unsignedFile, $dataToSign);
        fclose($unsignedFile);
        $signedFile = fopen($this->getSignedDataFilePath() . 'signed.txt', "w");
        fwrite($signedFile, "");
        fclose($signedFile);
        openssl_pkcs7_sign(
            $this->getUnsignedDataFilePath(),
            $this->getSignedDataFilePath(),
            'file://' . $this->settings['certificate_path'],
            array('file://' . $this->settings['certificate_path'], $this->settings['certificate_password']),
            array(),
            PKCS7_NOSIGS
        );
        $sigendData = file_get_contents($this->getSignedDataFilePath());
        $sigendDataParts = explode("\n\n", $sigendData, 2);
        $signedDataFirstPart = $sigendDataParts[1];
        return explode("\n\n", $signedDataFirstPart, 2)[0];
    }

    private function callApi(string $url, array $data)
    {
        $headers = $this->getRequestHeaders();
        $response = Http::withHeaders($headers)->post($url, $data);
        if ($response->successful()) {
            return $response->json();
        }
        throw new HttpRequestFailedException($response->body(), $response->status());
    }

    private function getLoginData(): array
    {
        if (empty($this->settings['username'])) {
            throw new InvalidConfigurationException('Username has not been set.');
        }
        if (empty($this->settings['password'])) {
            throw new InvalidConfigurationException('Password has not been set.');
        }

        return [
            'UserName' => $this->settings['username'],
            'Password' => $this->settings['password']
        ];
    }

    private function getAuthData(): array
    {
        return [
            'SessionId' => cache($this->getSessionIdCacheKey()),
            'UserId' => $this->settings['username'],
            'Password' => $this->settings['password']
        ];
    }

    protected function getPurchaseData(): array
    {
        $phoneNumber = $this->invoice->getPhoneNumber();
        if (!empty($phoneNumber)) {
            $phoneNumber = $this->checkPhoneNumberFormat($phoneNumber);
        }

        return [
            'WSContext' => $this->getAuthData(),
            'TransType' => static::BANK_BUY_TRANSACTION_TYPE,
            'ReserveNum' => $this->invoice->getInvoiceId(),
            'Amount' => $this->invoice->getAmount(),
            'RedirectUrl' => $this->settings['callback_url'],
            'MobileNo' => $phoneNumber,
            'Email' => $this->invoice->getEmail(),
            'UserId' => $this->invoice->getUserId(),
        ];
    }

    protected function getVerificationData(): array
    {
        return [
            'WSContext' => $this->getAuthData(),
            'Token' => request('token', $this->invoice->getToken()),
            'RefNum' => request('RefNum', $this->invoice->getTransactionId())
        ];
    }

    protected function getStatusMessage($statusCode): string
    {
        $messages = [
            'erSucceed' => 'سرویس با موفقیت اجرا شد.',
            'erAAS_UseridOrPassIsRequired' => 'کد کاربری و رمز الزامی هست.',
            'erAAS_InvalidUseridOrPass' => 'کد کاربری و رمز صحیح نمی باشد.',
            'erAAS_InvalidUserType' => 'نوع کاربر نمی باشد.',
            'erAAS_UserExpired' => 'کاربر منقضی شده است.',
            'erAAS_UserNotActive' => 'کاربر غیرفعال است.',
            'erAAS_UserTemporaryInActive' => 'کاربر موقتا غیرفعال شده است.',
            'erAAS_UserSessionGenerateError' => 'خطا در تولید شناسه لاگین',
            'erAAS_UserPassMinLengthError' => 'حداقل طول رمز رعایت نشده است.',
            'erAAS_UserPassMaxLengthError' => 'حداکثر طول رمز رعایت نشده است.',
            'erAAS_InvalidUserCertificate' => 'برای کاربر فایل سرتیفکیت تعریف نشده است.',
            'erAAS_InvalidPasswordChars' => 'کاراکترهای غیرمجاز در رمز',
            'erAAS_InvalidSession' => 'شناسه لاگین معتبر نمی باشد.',
            'erAAS_InvalidChannelId' => 'کانال معتبر نمی باشد.',
            'erAAS_InvalidParam' => 'پارامترها معتبر نمی باشد.',
            'erAAS_NotAllowedToService' => 'کاربر مجوز سرویس را ندارد.',
            'erAAS_SessionIsExpired' => 'شناسه لاگین معتبر نمی باشد.',
            'erAAS_InvalidData' => 'داده ها معتبر نمی باشد.',
            'erAAS_InvalidSignature' => 'امضاء دیتا درست نمی باشد.',
            'erAAS_InvalidToken' => 'توکن معتبر نمی باشد.',
            'erAAS_InvalidSourceIp' => 'آدرس آی پی معتبر نمی باشد.',
            'erMts_ParamIsNull' => 'پارامترهای ورودی خالی می باشد.',
            'erMts_InvalidAmount' => 'مبلغ معتبر نمی باشد.',
            'erMts_InvalidGoodsReferenceIdLen' => 'طول شناسه خرید معتبر نمی باشد.',
            'erMts_InvalidMerchantGoodsReferenceIdLen' => 'طول شناسه خرید پذیرنده معتبر نمی باشد.',
            'erMts_InvalidMobileNo' => 'فرمت شماره موبایل معتبر نمی باشد.',
            'erMts_InvalidRedirectUrl' => 'طول یا فرمت آدرس صفحه رجوع معتبر نمی باشد.',
            'erMts_InvalidReferenceNum' => 'طول یا فرمت شماره رفرنس معتبر نمی باشد.',
            'erMts_InvalidRequestParam' => 'پارامترهای درخواست معتبر نمی باشد.',
            'erMts_InvalidReserveNum' => 'طول یا فرمت شماره رزرو معتبر نمی باشد.',
            'erMts_InvalidSessionId' => 'شناسه لاگین معتبر نمی باشد.',
            'erMts_InvalidSignature' => 'طول یا فرمت امضاء دیتا معتبر نمی باشد.',
            'erMts_InvalidTerminal' => 'کد ترمینال معتبر نمی باشد.',
            'erMts_InvalidToken' => 'توکن معتبر نمی باشد.',
            'erMts_InvalidUniqueId' => 'کد یکتا معتبر نمی باشد.',
            'erScm_InvalidAcceptor' => 'پذیرنده معتبر نمی باشد.',
        ];

        return array_key_exists($statusCode, $messages) ? $messages[$statusCode] : $statusCode;
    }

    protected function getSuccessResponseStatusCode(): string
    {
        return 'erSucceed';
    }

    protected function getLoginUrl(): string
    {
        return $this->getBaseRestServiceUrl() . 'merchantLogin/';
    }

    protected function getPurchaseUrl(): string
    {
        return $this->getBaseRestServiceUrl() . 'generateTransactionDataToSign/';
    }

    protected function getTokenGenerationUrl(): string
    {
        return $this->getBaseRestServiceUrl() . 'generateSignedDataToken/';
    }

    protected function getPaymentUrl(): string
    {
        return 'https://pna.shaparak.ir/_ipgw_//payment/';
    }

    protected function getVerificationUrl(): string
    {
        return $this->getBaseRestServiceUrl() . 'verifyMerchantTrans/';
    }

    private function getBaseRestServiceUrl(): string
    {
        return 'https://pna.shaparak.ir/ref-payment2/RestServices/mts/';
    }

    private function getRequestHeaders(): array
    {
        return config('gateway_novin.request_headers', [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    }

    private function getSessionIdCacheKey(): string
    {
        return 'novin_gateway_session_id';
    }

    private function getLanguage(): string
    {
        return config('gateway_novin.language', 'fa');
    }

    private function checkPhoneNumberFormat(string $phoneNumber): string
    {
        if (strlen($phoneNumber) == 11 and Str::startsWith($phoneNumber, '09')) {
            return $phoneNumber;
        }
        if (strlen($phoneNumber) == 12 and Str::startsWith($phoneNumber, '98')) {
            return Str::replaceFirst('98', '0', $phoneNumber);
        }
        if (strlen($phoneNumber) == 10 and Str::startsWith($phoneNumber, '9')) {
            return '0' . $phoneNumber;
        }

        return $phoneNumber;
    }

    private function getUnsignedDataFilePath(): string
    {
        return $this->settings['temp_files_dir'] . 'unsigned.txt';
    }

    private function getSignedDataFilePath()
    {
        return $this->settings['temp_files_dir'] . 'signed.txt';
    }
}
