<?php

namespace Omalizadeh\MultiPayment\Drivers\Pasargad;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Omalizadeh\MultiPayment\RedirectionForm;
use Omalizadeh\MultiPayment\Drivers\Contracts\Driver;
use Omalizadeh\MultiPayment\Drivers\Pasargad\Helpers\RSAProcessor;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;

class Pasargad extends Driver
{
    const BANK_BUY_ACTION_CODE = 1003;

    public function purchase(): string
    {
        $data = $this->getPurchaseData();
        $response = $this->callApi($this->getPurchaseUrl(), $data);
        if ($response->successful()) {
            $result = $response->json();
            if ($result['IsSuccess'] != $this->getSuccessResponseStatusCode()) {
                throw new PurchaseFailedException($result['Message'], $response->status());
            }
            $this->invoice->setToken($result['Token']);

            return $data['InvoiceNumber'];
        }
        throw new PurchaseFailedException($response->body(), $response->status());
    }

    public function pay(): RedirectionForm
    {
        $payUrl = $this->getPaymentUrl();
        $data = [
            'Token' => $this->invoice->getToken()
        ];

        return $this->redirectWithForm($payUrl, $data);
    }

    public function verify(): string
    {
        $checkTransactionData = [
            'TransactionReferenceID' => request('tref')
        ];
        $response = $this->callApi($this->getCheckTransactionUrl(), $checkTransactionData);
        if ($response->successful()) {
            $checkTransactionResult = $response->json();
            if ($checkTransactionResult['IsSuccess'] != $this->getSuccessResponseStatusCode()) {
                throw new PaymentFailedException($checkTransactionResult['Message'], $response->status());
            }
            $verificationData = array_merge($this->getVerificationData(), [
                'InvoiceNumber' => $checkTransactionResult['InvoiceNumber'],
                'InvoiceDate' => $checkTransactionResult['InvoiceDate'],
                'Amount' => $checkTransactionResult['Amount'],
            ]);
            $response = $this->callApi($this->getVerificationUrl(), $verificationData);
            if ($response->successful()) {
                $verificationResult = $response->json();
                if ($verificationResult['IsSuccess'] != $this->getSuccessResponseStatusCode()) {
                    throw new PaymentFailedException($verificationResult['Message'], $response->status());
                }
                $this->invoice->setTransactionId($checkTransactionResult['TransactionReferenceID']);
                $this->invoice->setReferenceId($checkTransactionResult['ReferenceNumber']);
                $this->invoice->setCardNo($verificationResult['MaskedCardNumber']);

                return $checkTransactionResult['TraceNumber'];
            }
            throw new PaymentFailedException($response->body(), $response->status());
        }
        throw new PaymentFailedException($response->body(), $response->status());
    }

    private function callApi(string $url, array $data)
    {
        $body = json_encode($data);
        $sign = $this->signData($body);
        $headers = $this->getRequestHeaders();
        $headers = array_merge($headers, [
            'Sign' => $sign
        ]);
        $response = Http::withHeaders($headers)->post($url, $data);

        return $response;
    }

    private function signData(string $stringifiedData)
    {
        $stringifiedData = sha1($stringifiedData, true);
        $certificate = $this->settings['certificate'];
        $certificateType = $this->settings['certificate_type'];
        $processor = new RSAProcessor($certificate, $certificateType);
        $data = $processor->sign($stringifiedData);

        return base64_encode($data);
    }

    protected function getPurchaseData(): array
    {
        if (empty($this->settings['merchant_code'])) {
            throw new InvalidConfigurationException('Merchant code has not been set.');
        }
        if (empty($this->settings['terminal_code'])) {
            throw new InvalidConfigurationException('Terminal code has not been set.');
        }
        $mobile = $this->invoice->getPhoneNumber();
        if (!empty($mobile)) {
            $mobile = $this->checkPhoneNumberFormat($mobile);
        }

        return [
            'Action' => static::BANK_BUY_ACTION_CODE,
            'MerchantCode' => $this->settings['merchant_code'],
            'TerminalCode' => $this->settings['terminal_code'],
            'RedirectAddress' => $this->settings['callback_url'],
            'Amount' => $this->invoice->getAmount(),
            'Mobile' => $mobile,
            'Email' => $this->invoice->getEmail(),
            'InvoiceNumber' => $this->invoice->getInvoiceId(),
            'InvoiceDate' => now()->toDateString(),
            'Timestamp' => now()->format('Y/m/d H:i:s'),
        ];
    }

    protected function getVerificationData(): array
    {
        return [
            'MerchantCode' => $this->settings['merchant_code'],
            'TerminalCode' => $this->settings['terminal_code'],
            'Timestamp' => now()->format('Y/m/d H:i:s'),
        ];
    }

    protected function getStatusMessage($status): string
    {
        return "خطا در تبادل اطلاعات با درگاه";
    }

    protected function getSuccessResponseStatusCode()
    {
        return true;
    }

    protected function getPurchaseUrl(): string
    {
        return "https://pep.shaparak.ir/Api/v1/Payment/GetToken";
    }

    protected function getPaymentUrl(): string
    {
        return "https://pep.shaparak.ir/payment.aspx";
    }

    protected function getVerificationUrl(): string
    {
        return "https://pep.shaparak.ir/Api/v1/Payment/VerifyPayment";
    }

    private function getCheckTransactionUrl(): string
    {
        return "https://pep.shaparak.ir/Api/v1/Payment/CheckTransactionResult";
    }

    private function getRequestHeaders(): array
    {
        return config('gateway_pasargad.request_headers', [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    }

    private function checkPhoneNumberFormat(string $phoneNumber): string
    {
        if (strlen($phoneNumber) == 12 and Str::startsWith($phoneNumber, '98')) {
            return Str::replaceFirst('98', '', $phoneNumber);
        }
        if (strlen($phoneNumber) == 11 and Str::startsWith($phoneNumber, '0')) {
            return Str::replaceFirst('0', '', $phoneNumber);
        }
        return $phoneNumber;
    }
}
