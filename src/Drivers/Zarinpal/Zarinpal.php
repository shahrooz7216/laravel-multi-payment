<?php

namespace Omalizadeh\MultiPayment\Drivers\Zarinpal;

use Illuminate\Support\Facades\Http;
use Omalizadeh\MultiPayment\RedirectionForm;
use Omalizadeh\MultiPayment\Drivers\Contracts\Driver;
use Omalizadeh\MultiPayment\Drivers\Contracts\UnverifiedPaymentsInterface;
use Omalizadeh\MultiPayment\Exceptions\HttpRequestFailedException;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Exceptions\InvalidGatewayResponseDataException;
use Omalizadeh\MultiPayment\Exceptions\PaymentAlreadyVerifiedException;
use Omalizadeh\MultiPayment\Receipt;

class Zarinpal extends Driver implements UnverifiedPaymentsInterface
{
    public function purchase(): string
    {
        $response = $this->callApi($this->getPurchaseUrl(), $this->getPurchaseData());
        if ($response['data']['code'] !== $this->getSuccessResponseStatusCode() or empty($response['data']['authority'])) {
            $message = $this->getStatusMessage($response['data']['code']);
            throw new PurchaseFailedException($message, $response['data']['code']);
        }
        $this->getInvoice()->setTransactionId($response['data']['authority']);

        return $response['data']['authority'];
    }

    public function pay(): RedirectionForm
    {
        $transactionId = $this->getInvoice()->getTransactionId();
        $paymentUrl = $this->getPaymentUrl();
        if (strtolower($this->getMode()) == 'zaringate') {
            $payUrl = str_replace(':authority', $transactionId, $paymentUrl);
        } else {
            $payUrl = $paymentUrl . $transactionId;
        }

        return $this->redirect($payUrl, [], 'GET');
    }

    public function verify(): Receipt
    {
        $status = request('Status');

        if ($status !== 'OK') {
            throw new PaymentFailedException('عملیات پرداخت ناموفق بود یا توسط کاربر لغو شد.');
        }

        $response = $this->callApi($this->getVerificationUrl(), $this->getVerificationData());
        $responseCode = $response['data']['code'];

        if ($responseCode !== $this->getSuccessResponseStatusCode()) {
            $message = $this->getStatusMessage($responseCode);
            if ($responseCode == $this->getPaymentAlreadyVerifiedStatusCode()) {
                throw new PaymentAlreadyVerifiedException($message, $responseCode);
            }
            throw new PaymentFailedException($message, $responseCode);
        }

        $refId = $response['data']['ref_id'];

        $this->getInvoice()->setReferenceId($refId);

        return new Receipt($this->getInvoice(), $refId, $refId);
    }

    public function latestUnverifiedPayments(): array
    {
        $response = $this->callApi($this->getUnverifiedPaymentsUrl(), $this->getUnverifiedPaymentsData());
        if ($response['data']['code'] !== $this->getSuccessResponseStatusCode()) {
            $message = $this->getStatusMessage($response['data']['code']);
            throw new InvalidGatewayResponseDataException($message, $response['data']['code']);
        }

        return $response['data']['authorities'];
    }

    protected function getPurchaseData(): array
    {
        if (empty($this->settings['merchant_id'])) {
            throw new InvalidConfigurationException('Merchant id has not been set.');
        }
        if (!empty($this->getInvoice()->getDescription())) {
            $description = $this->getInvoice()->getDescription();
        } else {
            $description = $this->settings['description'];
        }
        $mobile = $this->getInvoice()->getPhoneNumber();
        $email = $this->getInvoice()->getEmail();

        return [
            'merchant_id' => $this->settings['merchant_id'],
            'amount' => $this->getInvoice()->getAmount(),
            'callback_url' => $this->settings['callback_url'],
            'description' => $description,
            'meta_data' => [
                'mobile' => $mobile,
                'email' => $email
            ]
        ];
    }

    protected function getVerificationData(): array
    {
        $authority = request('Authority', $this->getInvoice()->getTransactionId());

        return [
            'merchant_id' => $this->settings['merchant_id'],
            'authority' => $authority,
            'amount' => $this->getInvoice()->getAmount(),
        ];
    }

    protected function getUnverifiedPaymentsData(): array
    {
        if (empty($this->settings['merchant_id'])) {
            throw new InvalidConfigurationException('Merchant id has not been set.');
        }

        return [
            'merchant_id' => $this->settings['merchant_id'],
        ];
    }

    protected function getStatusMessage($statusCode): string
    {
        $messages = [
            -1 => "اطلاعات ارسال شده ناقص است.",
            -2 => "IP و يا مرچنت كد پذيرنده صحيح نيست",
            -3 => "با توجه به محدوديت هاي شاپرك امكان پرداخت با رقم درخواست شده ميسر نمي باشد",
            -4 => "سطح تاييد پذيرنده پايين تر از سطح نقره اي است.",
            -11 => "درخواست مورد نظر يافت نشد.",
            -12 => "امكان ويرايش درخواست ميسر نمي باشد.",
            -21 => "هيچ نوع عمليات مالي براي اين تراكنش يافت نشد",
            -22 => "تراكنش نا موفق ميباشد",
            -33 => "رقم تراكنش با رقم پرداخت شده مطابقت ندارد",
            -34 => "سقف تقسيم تراكنش از لحاظ تعداد يا رقم عبور نموده است",
            -40 => "اجازه دسترسي به متد مربوطه وجود ندارد.",
            -41 => "اطلاعات ارسال شده مربوط به AdditionalData غيرمعتبر ميباشد.",
            -42 => "مدت زمان معتبر طول عمر شناسه پرداخت بايد بين 30 دقيه تا 45 روز مي باشد.",
            -54 => "درخواست مورد نظر آرشيو شده است",
            101 => "عمليات پرداخت موفق بوده و قبلا PaymentVerification تراكنش انجام شده است.",
        ];
        $unknownError = 'خطای ناشناخته رخ داده است.';

        return array_key_exists($statusCode, $messages) ? $messages[$statusCode] : $unknownError;
    }

    protected function getSuccessResponseStatusCode(): int
    {
        return 100;
    }

    private function getPaymentAlreadyVerifiedStatusCode(): int
    {
        return 101;
    }

    protected function getPurchaseUrl(): string
    {
        $mode = $this->getMode();
        switch ($mode) {
            case 'sandbox':
                $url = 'https://sandbox.zarinpal.com/pg/v4/payment/request.json';
                break;
            default:
                $url = 'https://api.zarinpal.com/pg/v4/payment/request.json';
                break;
        }

        return $url;
    }

    protected function getPaymentUrl(): string
    {
        $mode = $this->getMode();
        switch ($mode) {
            case 'zaringate':
                $url = 'https://zarinpal.com/pg/StartPay/:authority/ZarinGate';
                break;
            case 'sandbox':
                $url = 'https://sandbox.zarinpal.com/pg/StartPay/';
                break;
            default:
                $url = 'https://zarinpal.com/pg/StartPay/';
                break;
        }

        return $url;
    }

    protected function getVerificationUrl(): string
    {
        $mode = $this->getMode();
        switch ($mode) {
            case 'sandbox':
                $url = 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json';
                break;
            default:
                $url = 'https://api.zarinpal.com/pg/v4/payment/verify.json';
                break;
        }

        return $url;
    }

    protected function getUnverifiedPaymentsUrl(): string
    {
        return 'https://api.zarinpal.com/pg/v4/payment/unVerified.json';
    }

    private function getMode(): string
    {
        return strtolower(trim($this->settings['mode']));
    }

    private function getRequestHeaders(): array
    {
        return config('gateway_zarinpal.request_headers', [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
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
}
