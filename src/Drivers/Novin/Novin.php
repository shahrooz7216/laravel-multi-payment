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
            $tokenGenerationData = [
                'WSContext' => $this->getAuthData(),
                'Signature' => $dataToSign,
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
        throw new PaymentFailedException($response['Result']);
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

    protected function getLoginData(): array
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

    protected function getAuthData(): array
    {
        return array_merge($this->getLoginData(), [
            'SessionId' => cache($this->getSessionIdCacheKey()),
        ]);
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
            'Token' => request('Token') ?? $this->invoice->getToken(),
            'RefNum' => request('RefNum') ?? $this->invoice->getTransactionId()
        ];
    }

    protected function getStatusMessage($statusCode): string
    {
        $messages = [
        ];
        $unknownError = 'خطای ناشناخته رخ داده است.';

        return array_key_exists($statusCode, $messages) ? $messages[$statusCode] : $unknownError;
    }

    protected function getSuccessResponseStatusCode(): string
    {
        return 'erSucceed';
    }

    protected function getLoginUrl(): string
    {
        return $this->getBaseRestServiceUrl() . 'merchantLogin';
    }

    protected function getPurchaseUrl(): string
    {
        return $this->getBaseRestServiceUrl() . 'generateTransactionDataToSign';
    }

    protected function getTokenGenerationUrl(): string
    {
        return $this->getBaseRestServiceUrl() . 'generateSignedDataToken';
    }

    protected function getPaymentUrl(): string
    {
        return 'https://pna.shaparak.ir/_ipgw_/payment';
    }

    protected function getVerificationUrl(): string
    {
        return $this->getBaseRestServiceUrl() . 'verifyMerchantTrans';
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
        return config('gateway_novin.session_id_cache_key', 'novin_gateway_session_id');
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
}
