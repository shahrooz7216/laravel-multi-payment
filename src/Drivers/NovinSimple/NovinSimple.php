<?php

namespace shahrooz7216\MultiPayment\Drivers\NovinSimple;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use shahrooz7216\MultiPayment\Drivers\Contracts\Driver;
use shahrooz7216\MultiPayment\Drivers\Contracts\RefundInterface;
use shahrooz7216\MultiPayment\Exceptions\HttpRequestFailedException;
use shahrooz7216\MultiPayment\Exceptions\InvalidConfigurationException;
use shahrooz7216\MultiPayment\Exceptions\PaymentFailedException;
use shahrooz7216\MultiPayment\Exceptions\PurchaseFailedException;
use shahrooz7216\MultiPayment\Exceptions\RefundFailedException;
use shahrooz7216\MultiPayment\Receipt;
use shahrooz7216\MultiPayment\RedirectionForm;

class NovinSimple extends Driver implements RefundInterface

{
    private function callApi(string $url, array $data)
    {
        $headers = $this->getRequestHeaders();

        $response = Http::withHeaders($headers)->post($url, $data);

        if ($response->successful()) {
            return $response->json();
        }

        throw new HttpRequestFailedException($response->body(), $response->status());
    }

    public function purchase(): string
    {
        $purchaseData = $this->getPurchaseData();
//		throw new PurchaseFailedException($this->getStatusMessage(json_encode($purchaseData)));
        $response = $this->callApi($this->getPurchaseUrl(), $purchaseData);

        if ($response['Status'] && $response['Status'] == $this->getSuccessResponseStatusCode())
        {
            $this->getInvoice()->setTransactionId($response['Token']);
            return  $response['Token'];
        }
        throw new PurchaseFailedException($this->getStatusMessage($response['Status']));
    }

    public function pay(): RedirectionForm
    {
        $paymentUrl = $this->getPaymentUrl();
        return $this->redirect($paymentUrl);
    }

    public function verify(): Receipt
    {
        if (!empty(request('Status')) && strtoupper(request('Status')) !== 'OK') {
            throw new PaymentFailedException('کاربر از انجام تراکنش منصرف شده است.');
        }

        $verificationData = $this->getVerificationData();
        $response = $this->callApi($this->getVerificationUrl(), $verificationData);

        if ($response['Result'] == $this->getSuccessResponseStatusCode() && $response['Amount'] == $this->getInvoice()->getAmount()) {
            $this->getInvoice()->setTransactionId(request('RefNum'));
            $this->getInvoice()->setInvoiceId(request('ResNum'));

            return new Receipt(
                $this->getInvoice(),
                request('RRN'),
                request('RRN'),
                request('CardNumberMasked'),
            );
        }

        throw new PaymentFailedException($this->getStatusMessage($response['Result']));
    }

    protected function getPurchaseData(): array
    {
        if (empty($this->settings['pin_code'])) {
            throw new InvalidConfigurationException('pin_code has not been set.');
        }

        return [
            'CorporationPin' => $this->settings['pin_code'],
            'OrderId' => $this->getInvoice()->getInvoiceId(),
            'Amount' => $this->getInvoice()->getAmount(),
            'CallBackUrl' => $this->settings['callback_url'],
            'Originator' => $this->getInvoice()->getPhoneNumber()
        ];
    }

    protected function getVerificationData(): array
    {
        return [
            'CorporationPin' => $this->settings['pin_code'],
            'Token' => $this->getInvoice()->getTransactionId()
        ];
    }

    protected function getStatusMessage($statusCode): string
    {
        $messages = [
            '-32768' => 'ﺧﻄﺎﻱ ﻧﺎﺷﻨﺎﺧﺘﻪ ﺭﺥ ﺩﺍﺩﻩ ﺍﺳﺖ ',
            '-32004 ' => 'ﺧﻄﺎ ﺩﺭ ﻓﺮﺍﺧﻮﺍﻧﻲ ﺳﺮﻭﻳﺲ ﺩﺭﺧﻮﺍﺳﺖ ﺧﺮﻳﺪ ﺗﺴﻬﻴﻢ ﺁﻓﻼﻳﻦ ',
            '-32003' => 'ﻣﺒﻠﻎ ﻛﻞ ﺑﺎ ﺟﻤﻊ ﻣﺒﺎﻟﻎ ﺗﺴﻬﻴﻢ ﺷﺪﻩ ﺑﺮﺍﺑﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-32002' => 'ﻣﺒﻠﻎ ﺩﺭ ﻳﻚ ﻳﺎ ﭼﻨﺪ ﺁﻳﺘﻢ ﺩﺍﺩﻩ ﺷﺪﻩ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-32001' => 'ﻟﻴﺴﺖ ﺷﻤﺎﺭﻩ ﺷﺒﺎﻫﺎ ﺧﺎﻟﻲ ﺍﺳﺖ',
            '-32000'=>'ﺧﻄﺎ ﺩﺭ ﻓﺮﺍﺧﻮﺍﻧﻲ ﺳﺮﻭﻳﺲ ﻛﻨﺘﺮﻝ ﺷﺒﺎﻱ ﺫﻳﻨﻔﻌﺎﻥ ﺗﺴﻬﻴﻢ ﺁﻓﻼﻳﻦ ',
            '-20000' => 'ﺑﺮﺧﻲ ﺍﺯ ﺷﺒﺎﻫﺎ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-1638' => 'ﻓﺮﺍﺧﻮﺍﻧﻲ ﺳﺮﻭﻳﺲ ﺩﺭﺧﻮﺍﺳﺖ ﺷﺎﺭﮊ ﺗﺎﭖ ﺁﭖ ﻧﺎﻣﻮﻓﻖ ﺑﻮﺩ ',
            '-1637' => 'Single-Phased UD Payment SW2 exception ',
            '-1636' => 'Single-Phased USSD Topup Charge Payment Exception ',
            '-1635' => 'Single-Phased USSD Sale Payment Exception ',
            '-1634' => 'Single-Phased USSD Bill Payment Exception ',
            '-1633' => 'Non of batch bill request items transaction was successful. ',
            '-1632' => 'No batch bill request items found to process ',
            '-1631' => 'Some of bill items transaction was successful. ',
            '-1630' => 'Failed to continue batch bill transactions, because of a not continuable status occured. ',
            '-1629' => 'Batch Transaction Exception. ',
            '-1628' => 'ﺍﻧﺪﻳﺲ ﭘﻴﻮﻧﺪ ﻛﺎﺭﺕ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ',
            '-1627' => 'ﺍﻧﺪﻳﺲ ﻛﺎﺭﺕ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ',
            '-1626' => 'ﺍﻧﺠﺎﻡ ﺗﺮﺍﻛﻨﺶ ﺭﻭﻱ ﺍﻳﻦ ﺩﺭﮔﺎﻩ ﻣﺠﺎﺯ ﻧﻤﻲ ﺑﺎﺷﺪ',
            '-1625' => 'ﺗﺮﺍﻛﻨﺸﻲ ﺑﺮﺍﻱ ﺩﺭﺧﻮﺍﺳﺖ ﺍﻧﺠﺎﻡ ﻧﺸﺪﻩ ﺍﺳﺖ ﻛﻪ ﻗﺎﺑﻞ ﺑﺮﮔﺸﺖ ﺑﺎﺷﺪ',
            '-1624' => 'ﺷﺎﺭﮊ ﻛﺎﺭﺕ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ',
            '-1623' => 'ﺍﺳﺘﺜﻨﺎﻱ ﻓﺮﺍﺧﻮﺍﻧﻲ ﺳﺮﻭﻳﺲ ﺻﺎﺩﺭﻛﻨﻨﺪﻩ',
            '-1622' => 'ﺍﺳﺘﺜﻨﺎ ﺩﺭ ﻛﻨﺘﺮﻝ ﻣﺤﺪﻭﺩﻳﺖ ﭘﺬﻳﺮﻧﺪﻩ ﺑﺮﺍﻱ ﺷﻤﺎﺭﻩ ﻛﺎﺭﺕ',
            '-1621'=>'ﺍﻧﺠﺎﻡ ﺗﺮﺍﻛﻨﺶ ﻣﺤﺪﻭﺩ ﺑﻪ ﻛﺎﺭﺕ ﻫﺎﻱ ﭘﺬﻳﺮﻧﺪﻩ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1620' => 'ﺗﺎﭖ ﺁﭖ ﺩﺭ ﺩﺭﮔﺎﻩ ﻧﺎﻣﻮﻓﻖ ﺑﻮﺩ. ﺟﻬﺖ ﺭﻓﻊ ﻣﻐﺎﻳﺮﺕ ﺑﺎ ﻣﺮﻛﺰ ﺗﻤﺎﺱ ﺑﻪ ﺷﻤﺎﺭﻩ 2318-021 ﺗﻤﺎﺱ ﺑﮕﻴﺮﻳﺪ ',
            '-1619' => 'ﺗﺎﭖ ﺁﭖ ﺩﺭ ﻭﺍﺳﻂ ﺳﺮﻭﻳﺲ ﻧﺎﻣﻮﻓﻖ ﺑﻮﺩ. ﺟﻬﺖ ﺭﻓﻊ ﻣﻐﺎﻳﺮﺕ ﺑﺎ ﻣﺮﻛﺰ ﺗﻤﺎﺱ ﺑﻪ ﺷﻤﺎﺭﻩ 2318-021 ﺗﻤﺎﺱ ﺑﮕﻴﺮﻳﺪ ',
            '-1618' => 'ﺗﺎﭖ ﺁﭖ ﺗﻮﺳﻂ ﺗﺎﻣﻴﻦ ﻛﻨﻨﺪﻩ ﻧﺎﻣﻮﻓﻖ ﺑﻮﺩ. ﺟﻬﺖ ﺭﻓﻊ ﻣﻐﺎﻳﺮﺕ ﺑﺎ ﻣﺮﻛﺰ ﺗﻤﺎﺱ ﺑﻪ ﺷﻤﺎﺭﻩ 2318-021 ﺗﻤﺎﺱ ﺑﮕﻴﺮﻳﺪ ',
            '-1617' => 'ﺧﻄﺎﻱ ﺑﺎﻧﻚ ﺩﺭ ﺷﺎﺭﮊ ﺗﺎﭘﺂﭖ. ﺑﻪ ﻛﺪ ﻭﺿﻌﻴﺖ ﺑﺎﻧﻚ ﻣﺮﺍﺟﻌﻪ ﺷﻮﺩ ',
            '-1616' => 'ﺧﻄﺎ ﺩﺭ ﭘﺮﺩﺍﺯﺵ ﺗﺮﺍﻛﻨﺶ ﭘﺮﺩﺍﺧﺖ ﺑﺎ ﻛﺪ QR ',
            '-1615' => 'ﺍﺳﺘﺜﻨﺎﻱ ﻧﺎﺷﻨﺎﺧﺘﻪ ﺩﺭ ﭘﺮﺩﺍﺧﺖ ',
            '-1614' => 'ﺧﻄﺎ ﺩﺭ ﺫﺧﻴﺮﻩ ﺩﺭﺧﻮﺍﺳﺖ ﭘﺮﺩﺍﺧﺖ ﺑﺎ ﻛﺪ QR ',
            '-1613' => 'ﺧﻄﺎ ﺩﺭ ﺑﺎﺯﻳﺎﺑﻲ ﺍﻃﻼﻋﺎﺕ ﭘﺬﻳﺮﻧﺪﻩ ﻛﺘﺎﺑﺨﺎﻧﻪ ﭘﺮﺩﺍﺧﺖ ﻣﻮﺑﺎﻳﻠﻲ ',
            '-1612' => 'ﺧﻄﺎ ﺩﺭ ﺍﻋﺘﺒﺎﺭﺳﻨﺠﻲ ﺩﺭﺧﻮﺍﺳﺖ ﭘﺮﺩﺍﺧﺖ ﺗﻮﺳﻂ ﻛﺘﺎﺑﺨﺎﻧﻪ ﭘﺮﺩﺍﺧﺖ ﻣﻮﺑﺎﻳﻠﻲ ',
            '-1611' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﺑﺎﺯﻳﺎﺑﻲ ﺷﻤﺎﺭﻩ ﺳﻔﺎﺭﺵ ﺟﺪﻳﺪ ',
            '-1610' => 'ﺧﻄﺎﻱ ﺑﺎﻧﻚ ﺩﺭ ﭘﺮﺩﺍﺧﺖ.ﺑﻪ ﻛﺪ ﻭﺿﻌﻴﺖ ﺑﺎﻧﻚ ﻣﺮﺍﺟﻌﻪ ﺷﻮﺩ ',
            '-1609' => 'ﺧﻄﺎﻱ ﺑﺎﻧﻚ ﺩﺭ ﭘﺮﺩﺍﺧﺖ. ﺑﻪ ﻛﺪ ﻭﺿﻌﻴﺖ ﺑﺎﻧﻚ ﻣﺮﺍﺟﻌﻪ ﺷﻮﺩ ',
            '-1608' => 'ﺧﻄﺎﻱ ﺑﺎﻧﻚ ﺩﺭ ﭘﺮﺩﺍﺧﺖ ﻗﺒﺾ.ﺑﻪ ﻛﺪ ﻭﺿﻌﻴﺖ ﺑﺎﻧﻚ ﻣﺮﺍﺟﻌﻪ ﺷﻮﺩ ',
            '-1607' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﭘﺮﺩﺍﺧﺖ ﻗﺒﺾ ﺑﺼﻮﺭﺕ ﺗﻚ ﻓﺎﺯ ',
            '-1606' => 'Iso Reversal failed ',
            '-1605' => 'Iso Advice failed ',
            '-1604' => 'ﺧﻄﺎ ﺩﺭ ﺑﻪ ﺭﻭﺯ ﺭﺳﺎﻧﻲ ﺍﻃﻼﻋﺎﺕ ﺗﺮﺍﻛﻨﺶ ﺍﺭﺳﺎﻟﻲ ﺑﻪ ﺳﻮﺋﻴﭻ ﺑﺎﻧﻜﻲ ',
            '-1603' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺗﺮﺍﻛﻨﺶ ﻣﻮﺑﺎﻳﻞ ﺧﺮﻳﺪ ﺳﺮﻭﻳﺲ ﭘﺮﺩﺍﺧﺖ ',
            '-1602'=>'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺗﺮﺍﻛﻨﺶ ﻣﻮﺑﺎﻳﻞ ﻗﺒﺾ ﺳﺮﻭﻳﺲ ﭘﺮﺩﺍﺧﺖ ',
            '-1601' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺗﺮﺍﻛﻨﺶ ﻣﻮﺑﺎﻳﻞ ﺧﺮﻳﺪ ﺳﺮﻭﻳﺲ ﭘﺮﺩﺍﺧﺖ ',
            '-1600' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺗﺮﺍﻛﻨﺶ ﺷﺎﺭﮊ ﺗﺎﭖ ﺁﭖ ﺳﺮﻭﻳﺲ ﭘﺮﺩﺍﺧﺖ ',
            '-1599' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺗﺮﺍﻛﻨﺶ ﺍﻳﻨﺘﺮﻧﺘﻲ ﻗﺒﺾ ﺳﺮﻭﻳﺲ ﭘﺮﺩﺍﺧﺖ ',
            '-1598' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺗﺮﺍﻛﻨﺶ ﺍﻳﻨﺘﺮﻧﺘﻲ ﺧﺮﻳﺪ ﺳﺮﻭﻳﺲ ﭘﺮﺩﺍﺧﺖ ',
            '-1597' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺑﺎﺯﻳﺎﺑﻲ ﺍﻃﻼﻋﺎﺕ ﺗﺮﺍﻛﻨﺶ ﺗﺎﭖ ﺁﭖ ﺳﺮﻭﻳﺲ ﭘﺮﺩﺍﺧﺖ ',
            '-1596' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺑﺎﺯﻳﺎﺑﻲ ﺍﻃﻼﻋﺎﺕ ﺗﺮﺍﻛﻨﺶ ﻗﺒﺾ ﺳﺮﻭﻳﺲ ﭘﺮﺩﺍﺧﺖ ',
            '-1595' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺑﺎﺯﻳﺎﺑﻲ ﺍﻃﻼﻋﺎﺕ ﺗﺮﺍﻛﻨﺶ ﺧﺮﻳﺪ ﺳﺮﻭﻳﺲ ﭘﺮﺩﺍﺧﺖ ',
            '-1594' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺑﺎﺯﻳﺎﺑﻲ ﺍﻃﻼﻋﺎﺕ ﺗﺮﺍﻛﻨﺶ ﺗﺎﭖ ﺁﭖ ﺍﻡ ﭘﻲ ﺍﻝ ',
            '-1593' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺑﺎﺯﻳﺎﺑﻲ ﺍﻃﻼﻋﺎﺕ ﺗﺮﺍﻛﻨﺶ ﻗﺒﺾ ﺍﻡ ﭘﻲ ﺍﻝ ',
            '-1592' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺑﺎﺯﻳﺎﺑﻲ ﺍﻃﻼﻋﺎﺕ ﺗﺮﺍﻛﻨﺶ ﺧﺮﻳﺪ ﺍﻡ ﭘﻲ ﺍﻝ ',
            '-1591' => 'ﺗﻮﻛﻦ ﻛﺎﺭﺕ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-1590' => 'ﺷﻤﺎﺭﻩ ﻣﻮﺑﺎﻳﻞ ﺷﺎﺭﮊ ﺷﻮﻧﺪﻩ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-1589' => 'ﻧﻘﺾ ﻗﺎﻟﺐ ﻣﻘﺪﺍﺭ ﻋﺪﺩﻱ ',
            '-1588' => 'IsoWrapper.DoTransaction failure in TransactionRule ',
            '-1587' => 'ﺍﻃﻼﻋﺎﺕ ﺩﺭﺧﻮﺍﺳﺖ ﭘﺮﺩﺍﺧﺖ ﺩﺭ ﻣﻨﻄﻖ ﺗﺮﺍﻛﻨﺶ ﻳﺎﻓﺖ ﻧﺸﺪ ',
            '-1586' => 'ﺧﻄﺎ ﺩﺭ ﺗﺒﺪﻳﻞ ﭘﺎﺳﺦ ﺍﻳﺰﻭ ﺑﻪ ﺧﺮﻭﺟﻲ BusinessRule ',
            '-1585' => 'ﻋﻤﻠﻴﺎﺕ ﺑﺮﮔﺸﺖ ﺗﺮﺍﻛﻨﺶ ﺩﺭ ﺳﺮﻭﻳﺲ ﺑﺮﮔﺸﺖ ﺗﺮﺍﻛﻨﺶ ﻧﺎﻣﻮﻓﻖ ﺑﻮﺩ ',
            '-1584' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺗﺮﺍﻛﻨﺶ ﻣﻮﺑﺎﻳﻞ ﺧﺮﻳﺪ MPL ',
            '-1583'=>'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺗﺮﺍﻛﻨﺶ ﻣﻮﺑﺎﻳﻞ ﻗﺒﺾ MPL ',
            '-1582' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺗﺮﺍﻛﻨﺶ ﻣﻮﺑﺎﻳﻞ ﺧﺮﻳﺪ MPL ',
            '-1581' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺗﺮﺍﻛﻨﺶ ﺷﺎﺭﮊ ﺗﺎﭖ ﺁﭖ MPL ',
            '-1580' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺗﺮﺍﻛﻨﺶ ﺍﻳﻨﺘﺮﻧﺘﻲ ﻗﺒﺾ MPL ',
            '-1579' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺗﺮﺍﻛﻨﺶ ﺍﻳﻨﺘﺮﻧﺘﻲ ﺧﺮﻳﺪ MPL ',
            '-1578' => 'ﻓﺮﺍﺧﻮﺍﻧﻲ ﺳﺮﻭﻳﺲ ﺩﺭﺧﻮﺍﺳﺖ ﺧﺮﻳﺪ UD ﻧﺎﻣﻮﻓﻖ ﺑﻮﺩ ',
            '-1577' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺧﺮﻳﺪ ﺷﺎﺭﮊ ﺗﺎﭖ ﺁﭖ ﺑﺼﻮﺭﺕ ﺗﻚ ﻓﺎﺯ ',
            '-1576' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﺧﺮﻳﺪ ﺑﺼﻮﺭﺕ ﺗﻚ ﻓﺎﺯ ',
            '-1575' => 'ﺻﻔﺤﻪ ﺩﺭﮔﺎﻩ ﺩﺭ ﻛﻼﻳﻨﺖ ﺑﺎﺭﮔﺰﺍﺭﻱ ﺷﺪ ',
            '-1574' => 'ﺧﻄﺎ ﺩﺭ ﺍﺟﺮﺍﻱ ﻣﺘﺪ ﺍﻳﻨﺪﻛﺲ ﺻﻔﺤﻪ ﭘﺮﺩﺍﺧﺖ ',
            '-1573' => 'ﭘﺲ ﺍﺯ ﺍﺟﺮﺍﻱ ﻣﺘﺪ ﺍﻳﻨﺪﻛﺲ ﺻﻔﺤﻪ ﭘﺮﺩﺍﺧﺖ ',
            '-1572' => 'ﭘﻴﺶ ﺍﺯ ﺍﺟﺮﺍﻱ ﻣﺘﺪ ﺍﻳﻨﺪﻛﺲ ﺻﻔﺤﻪ ﭘﺮﺩﺍﺧﺖ ',
            '-1571' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﺑﺎﺭﮔﺬﺍﺭﻱ ﺻﻔﺤﻪ ﭘﺮﺩﺍﺧﺖ ',
            '-1570' => 'ﻓﺮﺁﻳﻨﺪ ﭘﺮﺩﺍﺧﺖ ﺩﺭ ﺻﻔﺤﻪ ﺩﺭﮔﺎﻩ ﭘﺮﺩﺍﺧﺖ ﺑﺎ ﺍﺷﻜﺎﻟﻲ ﻣﻮﺍﺟﻪ ﺷﺪ ',
            '-1569' => 'ﻛﺎﭘﭽﺎ ﻣﻌﺘﺒﺮ ﻧﺒﻮﺩ ',
            '-1568' => 'ﻣﺪﻝ ﺻﻔﺤﻪ ﭘﺮﺩﺍﺧﺖ ﻣﻌﺘﺒﺮ ﻧﺒﻮﺩ ',
            '-1567' => 'ﭘﺮﺩﺍﺧﺖ ﻗﺒﺾ ﺩﺍﺩﻩ ﺷﺪﻩ ﺑﻪ ﺩﻟﻴﻞ ﻣﺤﺪﻭﺩﻳﺖ ﺍﻣﻜﺎﻧﭙﺬﻳﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-1566' => 'SinglePhasedPayment_TransactionResponseIsNotValid',
            '-1565' => 'SinglePhasedBillPayment_Exception',
            '-1564' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﭘﺮﺩﺍﺧﺖ ﺗﻚ ﻓﺎﺯ ',
            '-1563' => 'ﺗﻤﺎﻣﻲ ﭘﺮﺩﺍﺧﺖ ﻫﺎ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1562' => 'ﺑﺨﺸﻲ ﺍﺯ ﻗﺒﻮﺽ ﭘﺮﺩﺍﺧﺖ ﺷﺪﻩ ﻭ ﺑﺨﺸﻲ ﺩﭼﺎﺭ ﻣﺸﻜﻞ ﺷﺪﻩ ﺍﺳﺖ ',
            '-1561' => 'ﺗﺎﻳﻴﺪﻩ ﺗﺮﺍﻛﻨﺶ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ -ﻣﺸﻜﻠﻲ ﺩﺭ ﺳﻴﺴﺘﻢ ﺭﺥ ﺩﺍﺩﻩ ﻟﻄﻔﺎﻣﺠﺪﺩﺍ ﺗﻼﺵ ﻧﻤﺎﻳﻴﺪ ',
            '-1560' => 'ﺻﻔﺤﻪ ﺩﺭﮔﺎﻩ ﭘﺮﺩﺍﺧﺖ ﺑﺎﺭﮔﺬﺍﺭﻱ ﺷﺪ ',
            '-1555' => 'ﻫﻴﭻ ﻳﻚ ﺍﺯ ﻗﺒﻮﺽ ﺩﺍﺩﻩ ﺷﺪﻩ ﻣﻌﺘﺒﺮ ﻧﺒﻮﺩ. ﻭﺿﻌﻴﺖ ﻗﺒﻮﺽ ﺭﺍ ﺑﺮﺭﺳﻲ ﻧﻤﺎﻳﻴﺪ ',
            '-1554' => 'ﺗﻌﺪﺍﺩﻱ ﺍﺯ ﻗﺒﻮﺽ ﺩﺍﺩﻩ ﺷﺪﻩ ﭘﺬﻳﺮﺵ ﺷﺪ. ﻭﺿﻌﻴﺖ ﻗﺒﻮﺽ ﺭﺍ ﺑﺮﺭﺳﻲ ﻧﻤﺎﻳﻴﺪ ',
            '-1553' => 'ﺧﻄﺎ ﺩﺭ ﺩﺭﺝ ﺩﺭﺧﻮﺍﺳﺖ ﻗﺒﺾ ﮔﺮﻭﻫﻲ ',
            '-1552' => 'ﺑﺮﮔﺸﺖ ﺗﺮﺍﻛﻨﺶ ﻣﺠﺎﺯ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-1551' => 'ﺑﺮﮔﺸﺖ ﺗﺮﺍﻛﻨﺶ ﻗﺒﻼً ﺍﻧﺠﺎﻡ ﺷﺪﻩ ﺍﺳﺖ ',
            '-1550' => 'ﺑﺮﮔﺸﺖ ﺗﺮﺍﻛﻨﺶ ﺩﺭ ﻭﺿﻌﻴﺖ ﺟﺎﺭﻱ ﺍﻣﻜﺎﻥ ﭘﺬﻳﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-1549' => 'ﺯﻣﺎﻥ ﻣﺠﺎﺯ ﺑﺮﺍﻱ ﺩﺭﺧﻮﺍﺳﺖ ﺑﺮﮔﺸﺖ ﺗﺮﺍﻛﻨﺶ ﺑﻪ ﺍﺗﻤﺎﻡ ﺭﺳﻴﺪﻩ ﺍﺳﺖ ',
            '-1548' => 'ﻓﺮﺍﺧﻮﺍﻧﻲ ﺳﺮﻭﻳﺲ ﺩﺭﺧﻮﺍﺳﺖ ﭘﺮﺩﺍﺧﺖ ﻗﺒﺾ ﻧﺎﻣﻮﻓﻖ ﺑﻮﺩ ',
            '-1547' => 'ﺗﺮﺍﻛﻨﺶ ﺑﺮﮔﺸﺖ ﺑﻪ ﺳﻮﺋﻴﭻ ﺍﺭﺳﺎﻝ ﺷﺪ ',
            '-1546' => 'ﺗﺮﺍﻛﻨﺶ ﺑﺮﮔﺸﺖ ﺩﺭ ﺍﻧﺘﻈﺎﺭ ﺑﺮﺍﻱ ﺍﺭﺳﺎﻝ ﺑﻪ ﺳﻮﺋﻴﭻ ﺍﺳﺖ ',
            '-1545' => 'ﺩﺭﺝ ﻭﺿﻌﻴﺖ ﺗﺮﺍﻛﻨﺶ ﭘﻴﺶ ﺍﺯ ﺍﺭﺳﺎﻝ ﺗﺮﺍﻛﻨﺶ ﺗﺎﻳﻴﺪﻳﻪ ﻧﺎﻣﻮﻓﻖ ﺑﻮﺩ ',
            '-1544' => 'ﺗﺮﺍﻛﻨﺶ ﺗﺎﻳﻴﺪﻳﻪ ﺑﺎ ﻣﻮﻓﻘﻴﺖ ﺑﻪ ﺳﻮﺋﻴﭻ ﺍﺭﺳﺎﻝ ﺷﺪ ',
            '-1543' => 'ﺩﺭﺝ ﻭﺿﻌﻴﺖ ﺗﺮﺍﻛﻨﺶ ﭘﻴﺶ ﺍﺯ ﺍﺭﺳﺎﻝ ﺗﺮﺍﻛﻨﺶ ﺗﺎﻳﻴﺪﻳﻪ ﻧﺎﻣﻮﻓﻖ ﺑﻮﺩ ',
            '-1542' => 'ﺗﺮﺍﻛﻨﺶ ﺗﺎﻳﻴﺪﻳﻪ ﺩﺭ ﺍﻧﺘﻈﺎﺭ ﺑﺮﺍﻱ ﺍﺭﺳﺎﻝ ﺍﺳﺖ ',
            '-1541' => 'ﺩﺭﺝ ﻭﺿﻌﻴﺖ ﺍﻧﺠﺎﻡ ﺗﺮﺍﻛﻨﺶ ﺩﺭ ﺳﻮﺋﻴﭻ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1540' => 'ﺗﺎﻳﻴﺪ ﺗﺮﺍﻛﻨﺶ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1539' => 'Payment is not successful to advice. ',
            '-1538' => 'ValidateBeforeConfirm failed ',
            '-1537' => 'An error occured in BeforeSaveRequest ',
            '-1536' => 'ﻓﺮﺍﺧﻮﺍﻧﻲ ﺳﺮﻭﻳﺲ ﺩﺭﺧﻮﺍﺳﺖ ﺷﺎﺭﮊ ﺗﺎﭖ ﺁﭖ ﻧﺎﻣﻮﻓﻖ ﺑﻮﺩ ',
            '-1535' => 'ﺩﺭﺝ ﻧﺸﺎﻧﮕﺮ ﺗﺄﻳﻴﺪ ﺗﺮﺍﻛﻨﺶ ﻣﻮﻓﻖ، ﻣﻮﻓﻘﻴﺖ ﺁﻣﻴﺰ ﻧﺒﻮﺩ ',
            '-1534' => 'Successful Audit Time was not found. ',
            '-1533' => 'ﺗﺮﺍﻛﻨﺶ ﻗﺒﻼً ﺗﺎﻳﻴﺪ ﺷﺪﻩ ﺍﺳﺖ ',
            '-1532' => 'ﺗﺮﺍﻛﻨﺶ ﺍﺯ ﺳﻮﻱ ﭘﺬﻳﺮﻧﺪﻩ ﺗﺎﻳﻴﺪ ﺷﺪ ',
            '-1531' => 'ﺗﺎﻳﻴﺪ ﺗﺮﺍﻛﻨﺶ ﻧﺎﻣﻮﻓﻖ ﺍﻣﻜﺎﻥ ﭘﺬﻳﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-1530' => 'ﭘﺬﻳﺮﻧﺪﻩ ﻣﺠﺎﺯ ﺑﻪ ﺗﺎﻳﻴﺪ ﺍﻳﻦ ﺗﺮﺍﻛﻨﺶ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-1529' => 'ﺑﺎﺯﻳﺎﺑﻲ ﺍﻃﻼﻋﺎﺕ ﭘﺮﺩﺍﺧﺖ ﺑﺮﺍﻱ ﺗﺎﻳﻴﺪ ﺗﺮﺍﻛﻨﺶ ﻧﺎﻣﻮﻓﻖ ﺑﻮﺩ ',
            '-1528' => 'ﺍﻃﻼﻋﺎﺕ ﭘﺮﺩﺍﺧﺖ ﻳﺎﻓﺖ ﻧﺸﺪ ',
            '-1527' => 'ﺍﻧﺠﺎﻡ ﻋﻤﻠﻴﺎﺕ ﺩﺭﺧﻮﺍﺳﺖ ﭘﺮﺩﺍﺧﺖ ﺗﺮﺍﻛﻨﺶ ﺧﺮﻳﺪ ﻧﺎﻣﻮﻓﻖ ﺑﻮﺩ ',
            '-1526' => 'ﺩﺭﺝ ﻣﺮﺣﻠﻪ ﭘﺎﻳﺎﻥ ﭘﺮﺩﺍﺧﺖ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1525' => 'EndPayment ',
            '-1524' => 'ﺩﺭﺝ ﺍﻃﻼﻋﺎﺕ ﭘﺮﺩﺍﺧﺖ ﺑﻴﻤﻪ ﭘﺎﺭﺳﻴﺎﻥ ﻧﺎﻣﻮﻓﻖ ﺑﻮﺩ ',
            '-1523' => 'ﭘﺮﺩﺍﺯﺵ ﻓﺮﺁﻳﻨﺪ ﺍﻧﺠﺎﻡ ﺗﺮﺍﻛﻨﺶ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1522' => 'Unable to find PaymentSwitchConfig record for current transaction request. ',
            '-1521' => 'Iso Creation failed. ',
            '-1520' => 'ﺩﺭﺝ ﻣﺮﺣﻠﻪ ﺁﻣﺎﺩﻩ ﺑﻮﺩﻥ ﺗﺮﺍﻛﻨﺶ ﺑﺮﺍﻱ ﺍﺭﺳﺎﻝ ﺑﻪ ﺳﻮﻳﭻ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1519' => 'ﺗﺮﺍﻛﻨﺶ ﺁﻣﺎﺩﻩ ﺍﺭﺳﺎﻝ ﺑﻪ ﺳﻮﺋﻴﭻ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1518' => 'ﺩﺭﺝ ﻣﺮﺣﻠﻪ ﺷﺮﻭﻉ ﭘﺮﺩﺍﺧﺖ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1517' => 'ﻛﺎﺭﺑﺮ ﺩﻛﻤﻪ ﭘﺮﺩﺍﺧﺖ ﺭﺍ ﻓﺸﺮﺩﻩ ﺍﺳﺖ ',
            '-1516' => 'ﺩﺭﺝ ﺍﻃﻼﻋﺎﺕ ﺗﺮﺍﻛﻨﺶ ﭘﺮﺩﺍﺧﺖ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1515' => 'ﺩﺭﺝ ﺍﻃﻼﻋﺎﺕ ﺩﺭﺧﻮﺍﺳﺖ ﭘﺮﺩﺍﺧﺖ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1514' => 'ﺩﺭﺝ ﻣﺮﺣﻠﻪ ﺑﺎﺭﮔﺬﺍﺭﻱ ﺻﻔﺤﻪ ﺩﺭﮔﺎﻩ ﭘﺮﺩﺍﺧﺖ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1513' => 'ﺩﺭﺝ ﻣﺮﺣﻠﻪ ﺍﺭﺳﺎﻝ ﺗﺮﺍﻛﻨﺶ ﺑﺮﮔﺸﺖ ﺑﻪ ﺳﻮﻳﭻ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1512' => 'ﺩﺭﺝ ﻣﺮﺣﻠﻪ ﺍﺭﺳﺎﻝ ﺗﺮﺍﻛﻨﺶ ﺗﺎﻳﻴﺪﻩ ﺑﻪ ﺳﻮﻳﺞ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1511' => 'ﺩﺭﺝ ﻣﺮﺣﻠﻪ ﺩﺭﺧﻮﺍﺳﺖ ﺗﺎﻳﻴﺪ ﺗﺮﺍﻛﻨﺶ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1510' => 'ﺩﺭﺝ ﻣﺮﺣﻠﻪ ﺍﺭﺟﺎﻉ ﺑﻪ ﺳﺎﻳﺖ ﭘﺬﻳﺮﻧﺪﻩ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1509' => 'ﺩﺭﺝ ﻣﺮﺣﻠﻪ ﺍﺭﺳﺎﻝ ﺗﺮﺍﻛﻨﺶ ﺑﻪ ﺳﻮﻳﭻ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1508' => 'ﺩﺭﺝ ﻣﺮﺣﻠﻪ ﺛﺒﺖ ﺩﺭﺧﻮﺍﺳﺖ ﺍﻭﻟﻴﻪ ﭘﺮﺩﺍﺧﺖ ﻧﺎﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1507' => 'ﺗﺮﺍﻛﻨﺶ ﺑﺮﮔﺸﺖ ﺑﻪ ﺳﻮﺋﻴﭻ ﺍﺭﺳﺎﻝ ﺷﺪ ',
            '-1506' => 'ﺗﺮﺍﻛﻨﺶ ﺗﺎﻳﻴﺪﻳﻪ ﺑﻪ ﺳﻮﺋﻴﭻ ﺍﺭﺳﺎﻝ ﺷﺪ ',
            '-1505' => 'ﺗﺎﻳﻴﺪ ﺗﺮﺍﻛﻨﺶ ﺗﻮﺳﻂ ﭘﺬﻳﺮﻧﺪﻩ ﺍﻧﺠﺎﻡ ﺷﺪ ',
            '-1504' => 'ﺩﺭﮔﺎﻩ ﭘﺮﺩﺍﺧﺖ ﺍﻳﻨﺘﺮﻧﺘﻲ ﺻﻔﺤﻪ ﭘﺮﺩﺍﺧﺖ ﺭﺍ ﺑﻪ ﭘﺬﻳﺮﻧﺪﻩ ﺍﺭﺟﺎﻉ ﺩﺍﺩ ',
            '-1503' => 'ﺗﺮﺍﻛﻨﺶ ﭘﺮﺩﺍﺧﺖ ﺑﻪ ﺳﻮﺋﻴﭻ ﺍﺭﺳﺎﻝ ﺷﺪ ',
            '-1502' => 'ﻛﺎﺭﺑﺮ ﻭﺍﺭﺩ ﺻﻔﺤﻪ ﺩﺭﮔﺎﻩ ﭘﺮﺩﺍﺧﺖ ﺍﻳﻨﺘﺮﺗﻲ ﺷﺪ ',
            '-1500' => 'ﺛﺒﺖ ﺍﻭﻟﻴﻪ ﺩﺭﺧﻮﺍﺳﺖ ',
            '-1006' => 'Can not get ISO Response Code from Message ',
            '-1005' => 'Iso Response Parse Error ',
            '-1004' => 'ﻃﻮﻝ ﺩﺍﺩﻩ ﺩﺭﻳﺎﻓﺘﻲ ﺍﺯ ﺳﻮﺋﻴﭻ ﻧﺎﻣﻌﺘﺒﺮ ﺍﺳﺖ ',
            '-1003' => 'ﺧﻄﺎ ﺩﺭ ﺍﻧﺠﺎﻡ ﺗﺮﺍﻛﻨﺶ ﺭﻭﻱ ﺳﻮﺋﻴﭻ ',
            '-1002' => 'ﺧﻄﺎ ﺩﺭ ﺍﺭﺳﺎﻝ ﺍﻃﻼﻋﺎﺕ ﺑﻪ ﺳﻮﺋﻴﭻ ',
            '-1001' => 'ﺧﻄﺎ ﺩﺭ ﺍﺗﺼﺎﻝ ﻭ ﻳﺎ ﺩﺭﻳﺎﻓﺖ ﺍﻃﻼﻋﺎﺕ ﺍﺯ ﺳﻮﺋﻴﭻ Network Timeout ',
            '-1000' => 'ﺧﻄﺎ ﺩﺭ ﺩﺭ ﻳﺎﻓﺖ ﺍﻃﻼﻋﺎﺕ ﺍﺯ ﺳﻮﺋﻴﭻ ',
            '-501' => 'ﺧﻄﺎ ﺩﺭ ﺑﺮﻗﺮﺍﺭﻱ ﺍﺭﺗﺒﺎﻁ ﺷﺒﻜﻪ ﺍﻱ ﺑﺎ ﺩﻳﺘﺎﺑﻴﺲ ',
            '-500' => 'ﺧﻄﺎ ﺩﺭ ﺩﺭﺝ ﺍﻃﻼﻋﺎﺕ ',
            '-155' => 'ﺍﺟﺎﺯﻩ ﭘﺮﺩﺍﺧﺖ ﺗﻚ ﻓﺎﺯ ﺍﻋﻄﺎ ﻧﺸﺪﻩ ﺍﺳﺖ ',
            '-154' => 'Either Card Number or Card Index parameters must be specified. ',
            '-153' => 'ﻗﺎﻟﺐ ﺷﻨﺎﺳﻪ ﺣﺴﺎﺏ ﺻﺤﻴﺢ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-152' => 'ﻋﺪﻡ ﻭﺟﻮﺩ ﺷﻨﺎﺳﻪ ﺣﺴﺎﺏ ﺩﺭ ﺩﺍﺩﻩ ﺍﺿﺎﻓﻲ ',
            '-148' => 'ﭘﺎﺭﺍﻣﺘﺮ ﻭﺭﻭﺩﻱ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-147' => 'ﻧﻮﻉ ﺷﺎﺭﮊ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-146' => 'ﺷﻤﺎﺭﻩ ﻣﻮﺑﺎﻳﻞ ﺑﺮﺍﻱ ﺷﺎﺭﮊ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-145' => 'ﺍﻗﻼﻡ ﺗﺴﻬﻴﻢ ﺗﻌﻴﻴﻦ ﻧﺸﺪﻩ ﺍﺳﺖ ',
            '-144' => 'ﻣﺒﻠﻎ ﺩﺭ ﺑﺮﺧﻲ ﺍﺯ ﺍﻗﻼﻡ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-143' => 'ﺟﻤﻊ ﻣﺒﺎﻟﻎ ﺗﺴﻬﻴﻢ ﺑﺎ ﻣﺒﻠﻎ ﺗﺮﺍﻛﻨﺶ ﻣﻐﺎﻳﺮﺕ ﺩﺍﺭﺩ ',
            '-142' => 'ﺷﻨﺎﺳﻪ ﻗﺒﺾ ﻳﺎ ﭘﺮﺩﺍﺧﺖ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-141' => 'ﻃﻮﻝ ﺭﻣﺰ ﺩﻭﻡ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-140' => 'ﺭﻣﺰ ﺩﻭﻡ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-139' => 'ﻛﺪ ﺍﻣﻨﻴﺘﻲ ﻭﺍﺭﺩ ﺷﺪﻩ ﺻﺤﻴﺢ ﻧﻤﻲ ﺑﺎﺵ ',
            '-138' => 'ﻋﻤﻠﻴﺎﺕ ﭘﺮﺩﺍﺧﺖ ﺗﻮﺳﻂ ﻛﺎﺭﺑﺮ ﻟﻐﻮ ﺷﺪ ',
            '-137' => 'DelegateCode does not end with CheckDigit. ',
            '-136' => 'DelegateCode length is less than or equal to CheckDigit. ',
            '-135' => 'CheckDigit ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-134' => 'DelegatePass ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-133' => 'DelegateCode ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-132' => 'ﻣﺒﻠﻎ ﺗﺮﺍﻛﻨﺶ ﻛﻤﺘﺮ ﺍﺯ ﺣﺪﺍﻗﻞ ﻣﺠﺎﺯ ﻣﻲ ﺑﺎﺷﺪ ',
            '-131' => 'Token ﻧﺎﻣﻌﺘﺒﺮ ﻣﻲ ﺑﺎﺷﺪ ',
            '-130' => 'ﺗﻮﻛﻦ ﻣﻨﻘﻀﻲ ﺷﺪﻩ ﺍﺳﺖ ',
            '-129' => 'ﻗﺎﻟﺐ ﺩﺍﺩﻩ ﻭﺭﻭﺩﻱ ﺻﺤﻴﺢ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-128' => 'ﻗﺎﻟﺐ ﺁﺩﺭﺱ IP ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-127' => 'ﺁﺩﺭﺱ ﺍﻳﻨﺘﺮﻧﺘﻲ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-126' => 'ﻛﺪ ﺷﻨﺎﺳﺎﻳﻲ ﭘﺬﻳﺮﻧﺪﻩ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-125' => 'ﻛﺪ CVV2 ﺻﺤﻴﺢ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-124' => 'ﻟﻄﻔﺎ ﻓﻴﻠﺪ ﺭﻣﺰ ﺍﻳﻨﺘﺮﻧﺘﻲ ﻛﺎﺭﺕ ﺭﺍ ﻛﺎﻣﻞ ﻛﻨﻴﺪ ',
            '-123' => 'ﺗﺎﺭﻳﺦ ﺍﻧﻘﻀﺎﻱ ﻛﺎﺭﺕ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-122' => 'ﺷﻤﺎﺭﻩ ﻛﺎﺭﺕ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-121' => 'ﺭﺷﺘﻪ ﺩﺍﺩﻩ ﺷﺪﻩ ﺑﻄﻮﺭ ﻛﺎﻣﻞ ﻋﺪﺩﻱ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-120' => 'ﻃﻮﻝ ﺩﺍﺩﻩ ﻭﺭﻭﺩﻱ ﻣﻌﺘﺒﺮ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-119' => 'ﺳﺎﺯﻣﺎﻥ ﻧﺎﻣﻌﺘﺒﺮ ﻣﻲ ﺑﺎﺷﺪ ',
            '-118' => 'ﻣﻘﺪﺍﺭ ﺍﺭﺳﺎﻝ ﺷﺪﻩ ﻋﺪﺩ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '-117' => 'ﻃﻮﻝ ﺭﺷﺘﻪ ﻛﻢ ﺗﺮ ﺍﺯ ﺣﺪ ﻣﺠﺎﺯ ﻣﻲ ﺑﺎﺷﺪ ',
            '-116' => 'ﻃﻮﻝ ﺭﺷﺘﻪ ﺑﻴﺶ ﺍﺯ ﺣﺪ ﻣﺠﺎﺯ ﻣﻲ ﺑﺎﺷﺪ ',
            '-115' => 'ﺷﻨﺎﺳﻪ ﭘﺮﺩﺍﺧﺖ ﻧﺎﻣﻌﺘﺒﺮ ﻣﻲ ﺑﺎﺷﺪ ',
            '-114' => 'ﺷﻨﺎﺳﻪ ﻗﺒﺾ ﻧﺎﻣﻌﺘﺒﺮ ﻣﻲ ﺑﺎﺷﺪ ',
            '-113' => 'ﭘﺎﺭﺍﻣﺘﺮ ﻭﺭﻭﺩﻱ ﺧﺎﻟﻲ ﻣﻲ ﺑﺎﺷﺪ ',
            '-112' => 'ﺷﻨﺎﺳﻪ ﺳﻔﺎﺭﺵ ﺗﻜﺮﺍﺭﻱ ﺍﺳﺖ ',
            '-111' => 'ﻣﺒﻠﻎ ﺗﺮﺍﻛﻨﺶ ﺑﻴﺶ ﺍﺯ ﺣﺪ ﻣﺠﺎﺯ ﭘﺬﻳﺮﻧﺪﻩ ﻣﻲ ﺑﺎﺷﺪ ',
            '-110' => 'ﻗﺎﺑﻠﻴﺖ OCP ﺑﺮﺍﻱ ﭘﺬﻳﺮﻧﺪﻩ ﻏﻴﺮ ﻓﻌﺎﻝ ﻣﻲ ﺑﺎﺷﺪ ',
            '-109' => 'ﻗﺎﺑﻠﻴﺖ ﭘﺮﺩﺍﺧﺖ ﺧﺮﻳﺪ ﻛﺎﻻﻱ ﺍﻳﺮﺍﻧﻲ ﺑﺮﺍﻱ ﭘﺬﻳﺮﻧﺪﻩ ﻏﻴﺮ ﻓﻌﺎﻝ ﻣﻲ ﺑﺎﺷﺪ ',
            '-108' => 'ﻗﺎﺑﻠﻴﺖ ﺑﺮﮔﺸﺖ ﺗﺮﺍﻛﻨﺶ ﺑﺮﺍﻱ ﭘﺬﻳﺮﻧﺪﻩ ﻏﻴﺮ ﻓﻌﺎﻝ ﻣﻲ ﺑﺎﺷﺪ ',
            '-107' => 'ﻗﺎﺑﻠﻴﺖ ﺍﺭﺳﺎﻝ ﺗﺎﻳﻴﺪﻩ ﺗﺮﺍﻛﻨﺶ ﺑﺮﺍﻱ ﭘﺬﻳﺮﻧﺪﻩ ﻏﻴﺮ ﻓﻌﺎﻝ ﻣﻲ ﺑﺎﺷﺪ ',
            '-106' => 'ﻗﺎﺑﻠﻴﺖ ﺷﺎﺭﮊ ﺑﺮﺍﻱ ﭘﺬﻳﺮﻧﺪﻩ ﻏﻴﺮ ﻓﻌﺎﻝ ﻣﻲ ﺑﺎﺷﺪ ',
            '-105' => 'ﻗﺎﺑﻠﻴﺖ ﺗﺎﭖ ﺁﭖ ﺑﺮﺍﻱ ﭘﺬﻳﺮﻧﺪﻩ ﻏﻴﺮ ﻓﻌﺎﻝ ﻣﻲ ﺑﺎﺷﺪ ',
            '-104' => 'ﻗﺎﺑﻠﻴﺖ ﭘﺮﺩﺍﺧﺖ ﻗﺒﺾ ﺑﺮﺍﻱ ﭘﺬﻳﺮﻧﺪﻩ ﻏﻴﺮ ﻓﻌﺎﻝ ﻣﻲ ﺑﺎﺷﺪ ',
            '-103' => 'ﻗﺎﺑﻠﻴﺖ ﺧﺮﻳﺪ ﺑﺮﺍﻱ ﭘﺬﻳﺮﻧﺪﻩ ﻏﻴﺮ ﻓﻌﺎﻝ ﻣﻲ ﺑﺎﺷﺪ ',
            '-102' => 'ﺗﺮﺍﻛﻨﺶ ﺑﺎ ﻣﻮﻓﻘﻴﺖ ﺑﺮﮔﺸﺖ ﺩﺍﺩﻩ ﺷﺪ ',
            '-101' => 'ﭘﺬﻳﺮﻧﺪﻩ ﺍﻫﺮﺍﺯ ﻫﻮﻳﺖ ﻧﺸﺪ ',
            '-100' => 'ﭘﺬﻳﺮﻧﺪﻩ ﻏﻴﺮﻓﻌﺎﻝ ﻣﻲ ﺑﺎﺷﺪ ',
            '-1' => 'ﺧﻄﺎﻱ ﺳﺮﻭﺭ ',
            '0' => 'ﻣﻮﻓﻖ ',
            '1' => 'ﺻﺎﺩﺭﻛﻨﻨﺪﻩ ﻱ ﻛﺎﺭﺕ ﺍﺯ ﺍﻧﺠﺎﻡ ﺗﺮﺍﻛﻨﺶ ﺻﺮﻑ ﻧﻈﺮ ﻛﺮﺩ ',
            '2' => 'ﻋﻤﻠﻴﺎﺕ ﺗﺎﻳﻴﺪﻳﻪ ﺍﻳﻦ ﺗﺮﺍﻛﻨﺶ ﻗﺒﻼ ﺑﺎﻣﻮﻓﻘﻴﺖ ﺻﻮﺭﺕ ﭘﺬﻳﺮﻓﺘﻪ ﺍﺳﺖ ',
            '3' => 'ﭘﺬﻳﺮﻧﺪﻩ ﻱ ﻓﺮﻭﺷﮕﺎﻫﻲ ﻧﺎﻣﻌﺘﺒﺮ ﻣﻲ ﺑﺎﺷﺪ ',
            '4' => 'ﻛﺎﺭﺕ ﺗﻮﺳﻂ ﺩﺳﺘﮕﺎﻩ ﺿﺒﻂ ﺷﻮﺩ ',
            '5' => 'ﺍﺯ ﺍﻧﺠﺎﻡ ﺗﺮﺍﻛﻨﺶ ﺻﺮﻑ ﻧﻈﺮ ﺷﺪ ',
            '6' => 'ﺑﺮﻭﺯ ﺧﻄﺎﻳﻲ ﻧﺎﺷﻨﺎﺧﺘﻪ ',
            '7' => 'ﺑﻪ ﺩﻟﻴﻞ ﺷﺮﺍﻳﻂ ﺧﺎﺹ ﻛﺎﺭﺕ ﺗﻮﺳﻂ ﺩﺳﺘﮕﺎﻩ ﺿﺒﻂ ﺷﻮﺩ ',
            '8' => 'ﺑﺎﺗﺸﺨﻴﺺ ﻫﻮﻳﺖ ﺩﺍﺭﻧﺪﻩ ﻱ ﻛﺎﺭﺕ، ﺗﺮﺍﻛﻨﺶ ﻣﻮﻓﻖ ﻣﻲ ﺑﺎﺷﺪ ',
            '9' => 'ﺩﺭﺧﻮﺍﺳﺖ ﺭﺳﻴﺪﻩ ﺩﺭ ﺣﺎﻝ ﭘﻲ ﮔﻴﺮﻱ ﻭ ﺍﻧﺠﺎﻡ ﺍﺳﺖ ',
            '10' => 'ﺗﺮﺍﻛﻨﺶ ﺑﺎ ﻣﺒﻠﻐﻲ ﭘﺎﻳﻴﻦ ﺗﺮ ﺍﺯ ﻣﺒﻠﻎ ﺩﺭﺧﻮﺍﺳﺘﻲ ( ﻛﻤﺒﻮﺩ ﭘﻮﻝ ATM ﻳﺎ ﺣﺴﺎﺏ ﻣﺸﺘﺮﻱ ) ﭘﺬﻳﺮﻓﺘﻪ ﺷﺪﻩ ﺍﺳﺖ ',
            '11' => 'ﺗﺮﺍﻛﻨﺶ ﺑﺎ ﻭﺟﻮﺩ ﺍﺣﺘﻤﺎﻟﻲ ﺑﺮﺧﻲ ﻣﺸﻜﻼﺕ ﭘﺬﻳﺮﻓﺘﻪ ﺷﺪﻩ ﺍﺳﺖ ( ﺑﻪ ﻋﻠﺖ ﭼﺎﻳﮕﺎﻩ ﻣﺸﺘﺮﻱ ( VIP ',
            '12' => 'ﺗﺮﺍﻛﻨﺶ ﻧﺎﻣﻌﺘﺒﺮ ﺍﺳﺖ ',
            '13' => 'ﻣﺒﻠﻎ ﺗﺮﺍﻛﻨﺶ ﺍﺻﻼﺣﻴﻪ ﻧﺎﺩﺭﺳﺖ ﺍﺳﺖ ',
            '14' => 'ﺷﻤﺎﺭﻩ ﻛﺎﺭﺕ ﺍﺭﺳﺎﻟﻲ ﻧﺎﻣﻌﺘﺒﺮ ﺍﺳﺖ (ﻭﺟﻮﺩ ﻧﺪﺍﺭﺩ) ',
            '15' => 'ﺻﺎﺩﺭﻛﻨﻨﺪﻩ ﻱ ﻛﺎﺭﺕ ﻧﺎﻣﻌﺘﺒﺮﺍﺳﺖ (ﻭﺟﻮﺩ ﻧﺪﺍﺭﺩ) ',
            '16' => 'ﺗﺮﺍﻛﻨﺶ ﻣﻮﺭﺩ ﺗﺎﻳﻴﺪ ﺍﺳﺖ ﻭ ﺍﻃﻼﻋﺎﺕ ﺷﻴﺎﺭ ﺳﻮﻡ ﻛﺎﺭﺕ ﺑﻪ ﺭﻭﺯ ﺭﺳﺎﻧﻲ ﺷﻮﺩ ',
            '17' => 'ﻣﺸﺘﺮﻱ ﺩﺭﺧﻮﺍﺳﺖ ﻛﻨﻨﺪﻩ ﺣﺬﻑ ﺷﺪﻩ ﺍﺳﺖ ',
            '18' => 'ﺩﺭ ﻣﻮﺍﻗﻌﻲ ﻛﻪ ﻳﻚ ﺗﺮﺍﻛﻨﺶ ﺑﻪ ﻫﺮ ﺩﻟﻴﻞ ﭘﺬﻳﺮﻓﺘﻪ ﻧﺸﺪﻩ ﺍﺳﺖ ﻭ ﻳﺎ ﺑﺎ ﺷﺮﺍﻳﻂ ﺧﺎﺻﻲ ﭘﺬﻳﺮﻓﺘﻪ ﺷﺪﻩ ﺍﺳﺖ ﺩﺭ ﺻﻮﺭﺕ ﺗﺎﻳﻴﺪ ﻭ ﻳﺎ ﺳﻤﺎﺟﺖ ﻣﺸﺘﺮﻱ ﺍﻳﻦ ﭘﻴﻐﺎﻡ ﺭﺍ ﺧﻮﺍﻫﻴﻢ ﺩﺍﺷﺘﭗ ',
            '19' => 'ﺗﺮﺍﻛﻨﺶ ﻣﺠﺪﺩﺍ ﺍﺭﺳﺎﻝ ﺷﻮﺩ ',
            '20' => 'ﺩﺭ ﻣﻮﻗﻌﻴﺘﻲ ﻛﻪ ﺳﻮﺋﻴﭻ ﺟﻬﺖ ﭘﺬﻳﺮﺵ ﺗﺮﺍﻛﻨﺶ ﻧﻴﺎﺯﻣﻨﺪ ﭘﺮﺱ ﻭ ﺟﻮ ﺍﺯ ﻛﺎﺭﺕ ﺍﺳﺖ ﻣﻤﻜﻦ ﺍﺳﺖ ﺩﺭﺧﻮﺍﺳﺖ ﺍﺯ ﻛﺎﺭﺕ ( ﺗﺮﻣﻴﻨﺎﻝ) ﺑﻨﻤﺎﻳﺪ ﺍﻳﻦ ﭘﻴﺎﻡ ﻣﺒﻴﻦ ﻧﺎﻣﻌﺘﺒﺮ ﺑﻮﺩﻥ ﺟﻮﺍﺏ ﺍﺳﺖ ',
            '21' => 'ﺩﺭ ﺻﻮﺭﺗﻲ ﻛﻪ ﭘﺎﺳﺦ ﺑﻪ ﺩﺭ ﺧﻮﺍﺳﺖ ﺗﺮﻣﻴﻨﺎ ﻝ ﻧﻴﺎﺯﻣﻨﺪ ﻫﻴﭻ ﭘﺎﺳﺦ ﺧﺎﺹ ﻳﺎ ﻋﻤﻠﻜﺮﺩﻱ ﻧﺒﺎﺷﻴﻢ ﺍﻳﻦ ﭘﻴﺎﻡ ﺭﺍ ﺧﻮﺍﻫﻴﻢ ﺩﺍﺷﺖ ',
            '22' => 'ﺗﺮﺍﻛﻨﺶ ﻣﺸﻜﻮﻙ ﺑﻪ ﺑﺪ ﻋﻤﻞ ﻛﺮﺩﻥ ( ﻛﺎﺭﺕ ، ﺗﺮﻣﻴﻨﺎﻝ ، ﺩﺍﺭﻧﺪﻩ ﻛﺎﺭﺕ ) ﺑﻮﺩﻩ ﺍﺳﺖ ﻟﺬﺍ ﭘﺬﻳﺮﻓﺘﻪ ﻧﺸﺪﻩ ﺍﺳﺖ ',
            '23' => 'ﻛﺎﺭﻣﺰﺩ ﺍﺭﺳﺎﻟﻲ ﭘﺬﻳﺮﻧﺪﻩ ﻏﻴﺮ ﻗﺎﺑﻞ ﻗﺒﻮﻝ ﺍﺳﺖ ',
            '24' => 'ﺯﻣﺎﻧﻲ ﻛﻪ ﻳﻚ ﺗﺮﺍﻛﻨﺶ ﻧﻴﺎﺯﻣﻨﺪ ﻋﻤﻞ ﻛﺮﺩ ﻳﺎ ﻓﺮﺍﺧﻮﺍﻧﻲ ﻓﺎﻳﻠﻲ ﺧﺎﺹ ﺑﺎﺷﺪ ﻭ ﻓﺎﻳﻞ ﻣﺬﻛﺮﻭ ﺩﺭ ﻣﺒﺪﺍ ﺩﺭﺧﻮﺍﺳﺖ ﻣﻮﺟﻮﺩ ﻧﺒﺎﺷﺪ ﺍﻳﻦ ﭘﻴﺎﻡ ﺭﺍ ﺧﻮﺍﻫﻴﻢ ﺩﺍﺷﺖ ',
            '25' => 'ﺗﺮﺍﻛﻨﺶ ﺍﺻﻠﻲ ﻳﺎﻓﺖ ﻧﺸﺪ ',
            '26' => 'ﻋﻤﻠﻴﺎﺕ ﻓﺎﻳﻞ ﺗﻜﺮﺍﺭﻱ ',
            '27' => 'ﺧﻄﺎ ﺩﺭ ﺍﺻﻼﺡ ﻓﻴﻠﺪ ﺍﻃﻼﻋﺎﺗﻲ ',
            '28' => 'ﻓﺎﻳﻞ ﻣﻮﺭﺩ ﻧﻈﺮ lock ﺷﺪﻩ ﺍﺳﺖ,',
            '29' => 'ﻋﻤﻠﻴﺎﺕ ﻓﺎﻳﻞ ﻧﺎﻣﻮﻓﻖ ',
            '30' => 'ﻗﺎﻟﺐ ﭘﻴﺎﻡ ﺩﺍﺭﺍﻱ ﺍﺷﻜﺎﻝ ﺍﺳﺖ ',
            '31' => 'ﭘﺬﻳﺮﻧﺪﻩ ﺗﻮﺳﻂ ﺳﻮﺋﻲ ﭘﺸﺘﻴﺒﺎﻧﻲ ﻧﻤﻲ ﺷﻮﺩ ',
            '32' => 'ﺗﺮﺍﻛﻨﺶ ﺑﻪ ﺻﻮﺭﺕ ﻏﻴﺮ ﻗﻄﻌﻲ ﻛﺎﻣﻞ ﺷﺪﻩ ﺍﺳﺖ ( ﺑﻪ ﻋﻨﻮﺍﻥ ﻣﺜﺎﻝ ﺗﺮﺍﻛﻨﺶ ﺳﭙﺮﺩﻩ ﮔﺰﺍﺭﻱ ﻛﻪ ﺍﺯ ﺩﻳﺪ ﻣﺸﺘﺮﻱ ﻛﺎﻣﻞ ﺷﺪﻩ ﺍﺳﺖ ﻭﻟﻲ ﻣﻲ ﺑﺎﻳﺴﺖ ﺗﻜﻤﻴﻞ ﮔﺮﺩﺩ ',
            '33' => 'ﺗﺎﺭﻳﺦ ﺍﻧﻘﻀﺎﻱ ﻛﺎﺭﺕ ﺳﭙﺮﻱ ﺷﺪﻩ ﺍﺳﺖ. ﻛﺎﺭﺕ ﺗﻮﺳﻂ ﺩﺳﺘﮕﺎﻩ ﺿﺒﻂ ﺷﻮﺩ ',
            '34' => 'ﺗﺮﺍﻛﻨﺶ ﺍﺻﻠﻲ ﺑﺎﻣﻮﻓﻘﻴﺖ ﺍﻧﺠﺎﻡ ﻧﭙﺬﻳﺮﻓﺘﻪ ﺍﺳﺖ ',
            '35' => 'ﺑﻨﺎﺑﺮ ﺗﻮﺻﻴﻪ ﻣﻮﺳﺴﻪ ﻳﺎ ﺑﺎﻧﻚ ﻣﺪﻳﺮ ﻛﺎﺭﺕ ﺑﻪ ﭘﺬﻳﺮﻧﺪﻩ ﻛﺎﺭﺕ ﺿﺒﻂ ﺷﺪﻩ ﺍﺳﺖ ',
            '36' => 'ﻛﺎﺭﺕ ﻣﺤﺪﻭﺩ ﺷﺪﻩ ﺍﺳﺖ. ﻛﺎﺭﺕ ﺗﻮﺳﻂ ﺩﺳﺘﮕﺎﻩ ﺿﺒﻂ ﺷﻮﺩ ',
            '37' => 'ﭘﺬﻳﺮﻧﺪﻩ ﺩﺭ ﻧﺘﻴﺠﻪ ﭼﻨﻴﻦ ﺩﺭﺧﻮﺍﺳﺘﻲ ﺑﺎ ﺑﺨﺶ ﺍﻣﻨﻴﺘﻲ ﻣﻮﺳﺴﻪ ﻳﺎ ﺑﺎﻧﻚ ﻣﺪﻳﺮ ﻛﺎﺭﺕ ﺗﻤﺎﺱ ﮔﺮﻓﺘﻪ ﺍﺳﺖ ( ﻳﺎ ﻣﻴﮕﻴﺮﺩ ) ',
            '38' => 'ﺗﻌﺪﺍﺩ ﺩﻓﻌﺎﺕ ﻭﺭﻭﺩ ﺭﻣﺰﻏﻠﻂ ﺑﻴﺶ ﺍﺯ ﺣﺪﻣﺠﺎﺯ ﺍﺳﺖ. ﻛﺎﺭﺕ ﺗﻮﺳﻂ ﺩﺳﺘﮕﺎﻩ ﺿﺒﻂ ﺷﻮﺩ ',
            '39' => 'ﻛﺎﺭﺕ ﺣﺴﺎﺏ ﺍﻋﺘﺒﺎﺭﻱ ﻧﺪﺍﺭﺩ ',
            '40' => 'ﻋﻤﻠﻴﺎﺕ ﺩﺭﺧﻮﺍﺳﺘﻲ ﭘﺸﺘﻴﺒﺎﻧﻲ ﻧﻤﻲ ﮔﺮﺩﺩ ',
            '41' => 'ﻛﺎﺭﺕ ﻣﻔﻘﻮﺩﻱ ﻣﻲ ﺑﺎﺷﺪ. ﻛﺎﺭﺕ ﺗﻮﺳﻂ ﺩﺳﺘﮕﺎﻩ ﺿﺒﻂ ﺷﻮﺩ ',
            '42' => 'ﻛﺎﺭﺕ ﺣﺴﺎﺏ ﻋﻤﻮﻣﻲ ﻧﺪﺍﺭﺩ ',
            '43' => 'ﻛﺎﺭﺕ ﻣﺴﺮﻭﻗﻪ ﻣﻲ ﺑﺎﺷﺪ. ﻛﺎﺭﺕ ﺗﻮﺳﻂ ﺩﺳﺘﮕﺎﻩ ﺿﺒﻂ ﺷﻮﺩ ',
            '44' => 'ﻛﺎﺭﺕ ﺣﺴﺎﺏ ﺳﺮﻣﺎﻳﻪ ﮔﺬﺍﺭﻱ ﻧﺪﺍﺭﺩ ',
            '45' => 'ﻗﺒﺾ ﻗﺎﺑﻞ ﭘﺮﺩﺍﺧﺖ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '51' => 'ﻣﻮﺟﻮﺩﻱ ﻛﺎﻓﻲ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '52' => 'ﻛﺎﺭﺕ ﺣﺴﺎﺏ ﺟﺎﺭﻱ ﻧﺪﺍﺭﺩ ',
            '53' => 'ﻛﺎﺭﺕ ﺣﺴﺎﺏ ﻗﺮﺽ ﺍﻟﺤﺴﻨﻪ ﻧﺪﺍﺭﺩ ',
            '54' => 'ﺗﺎﺭﻳﺦ ﺍﻧﻘﻀﺎﻱ ﻛﺎﺭﺕ ﺳﭙﺮﻱ ﺷﺪﻩ ﺍﺳﺖ ',
            '55' => 'ﺭﻣﺰ ﻛﺎﺭﺕ ﻧﺎ ﻣﻌﺘﺒﺮ ﺍﺳﺖ ',
            '56' => 'ﻛﺎﺭﺕ ﻧﺎ ﻣﻌﺘﺒﺮ ﺍﺳﺖ ',
            '57' => 'ﺍﻧﺠﺎﻡ ﺗﺮﺍﻛﻨﺶ ﻣﺮﺑﻮﻃﻪ ﺗﻮﺳﻂ ﺩﺍﺭﻧﺪﻩ ﻱ ﻛﺎﺭﺕ ﻣﺠﺎﺯ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '58' => 'ﺍﻧﺠﺎﻡ ﺗﺮﺍﻛﻨﺶ ﻣﺮﺑﻮﻃﻪ ﺗﻮﺳﻂ ﭘﺎﻳﺎﻧﻪ ﻱ ﺍﻧﺠﺎﻡ ﺩﻫﻨﺪﻩ ﻣﺠﺎﺯ ﻧﻤﻲ ﺑﺎﺷﺪ ',
            '59' => 'ﻛﺎﺭﺕ ﻣﻈﻨﻮﻥ ﺑﻪ ﺗﻘﻠﺐ ﺍﺳﺖ ',
            '60' => 'ﺑﻨﺎﺑﺮ ﺗﻮﺻﻴﻪ ﻣﻮﺳﺴﻪ ﻳﺎ ﺑﺎﻧﻚ ﻣﺪﻳﺮ ﻛﺎﺭﺕ ﺑﻪ ﭘﺬﻳﺮﻧﺪﻩ ﻛﺎﺭﺕ ، ﺗﺮﺍﻛﻨﺶ ﺩﺭﺧﻮﺍﺳﺘﻲ ﭘﺬﻳﺮﻓﺘﻪ ﻧﻤﻲ ﺷﻮﺩ ',
            '61' => 'ﻣﺒﻠﻎ ﺗﺮﺍﻛﻨﺶ ﻛﻤﺘﺮ ﺍﺯ ﺣﺪ ﺗﻌﻴﻴﻦ ﺷﺪﻩ ﺗﻮﺳﻂ ﺻﺎﺩﺭ ﻛﻨﻨﺪﻩ ﻛﺎﺭﺕ ﻭ ﻳﺎ ﺑﻴﺸﺘﺮ ﺍﺯ ﺣﺪ ﻣﺠﺎﺯ ﻣﻲ ﺑﺎﺷﺪ ',
            '62' => 'ﻛﺎﺭﺕ ﻣﺤﺪﻭﺩ ﺷﺪﻩ ﺍﺳﺖ ',
            '63' => 'ﺗﻤﻬﻴﺪﺍﺕ ﺍﻣﻨﻴﺘﻲ ﻧﻘﺾ ﮔﺮﺩﻳﺪﻩ ﺍﺳﺖ ',
            '64' => 'ﻣﺒﻠﻎ ﺗﺮﺍﻛﻨﺶ ﺍﺻﻠﻲ ﻥ ﺍﻣﻌﺘﺒﺮ ﺍﺳﺖ. (ﺗﺮﺍﻛﻨﺶ ﻣﺎﻟﻲ ﺍﺻﻠﻲ ﺑﺎ ﺍﻳﻦ ﻣﺒﻠﻎ ﻧﻤﻲ ﺑﺎﺷﺪ) ',
            '65' => 'ﺗﻌﺪﺍﺩ ﺩﺭﺧﻮﺍﺳﺖ ﺗﺮﺍﻛﻨﺶ ﺑﻴﺶ ﺍﺯ ﺣﺪ ﻣﺠﺎﺯ ﻣﻲ ﺑﺎﺷﺪ ',
            '66' => 'ﺩﺭ ﭘﻲ ﺗﺮﺍﻛﻨﺶ ﺩﺭﺧﻮﺍﺳﺘﻲ ﭘﺬﻳﺮﻧﺪﻩ ﺑﺎ ﺑﺨﺶ ﺍﻣﻨﻴﺘﻲ ﻣﻮﺳﺴﻪ ﻳﺎ ﺑﺎﻧﻚ ﺗﻤﺎﺱ ﮔﺮﻓﺘﻪ ﺍﺳﺖ ( ﻭ ﻳﺎ ﻣﻴﮕﻴﺮﺩ ) ',
            '67' => 'ﻛﺎﺭﺕ ﺗﻮﺳﻂ ﺩﺳﺘﮕﺎﻩ ﺿﺒﻂ ﺷﻮﺩ ',
            '68' => 'ﭘﺎﺳﺦ ﻻﺯﻡ ﺑﺮﺍﻱ ﺗﻜﻤﻴﻞ ﻳﺎ ﺍﻧﺠﺎﻡ ﺗﺮﺍﻛﻨﺶ ﺧﻴﻠﻲ ﺩﻳﺮ ﺭﺳﻴﺪﻩ ﺍﺳﺖ ',
            '69' => 'ﺗﻌﺪﺍﺩ ﺩﻓﻌﺎﺕ ﺗﻜﺮﺍﺭﺭﻣﺰ ﺍﺯ ﺣﺪ ﻣﺠﺎﺯ ﮔﺬﺷﺘﻪ ﺍﺳﺖ ',
            '75' => 'ﺗﻌﺪﺍﺩ ﺩﻓﻌﺎﺕ ﻭﺭﻭﺩ ﺭﻣﺰﻏﻠﻂ ﺑﻴﺶ ﺍﺯ ﺣﺪﻣﺠﺎﺯ ﺍﺳﺖ ',
            '76' => 'ﻣﺒﻠﻎ ﺍﻧﺘﻘﺎﻝ ﺩﺍﺩﻩ ﺷﺪﻩ ﻣﻌﺘﺒﺮ ﻧﻴﺴﺖ ',
            '77' => 'ﺭﻭﺯ ﻣﺎﻟﻲ ﺗﺮﺍﻛﻨﺶ ﻧﺎ ﻣﻌﺘﺒﺮ ﺍﺳﺖ ﻳﺎ ﻣﻬﻠﺖ ﺯﻣﺎﻥ ﺍﺭﺳﺎﻝ ﺍﺻﻼﺣﻴﻪ ﺑﻪ ﭘﺎﻳﺎﻥ ﺭﺳﻴﺪﻩ ﺍﺳﺖ ',
            '78' => 'ﻛﺎﺭﺕ ﻓﻌﺎﻝ ﻧﻴﺴﺖ ',
            '79' => 'ﺣﺴﺎﺏ ﻣﺘﺼﻞ ﺑﻪ ﻛﺎﺭﺕ ﻧﺎ ﻣﻌﺘﺒﺮ ﺍﺳﺖ ﻳﺎ ﺩﺍﺭﺍﻱ ﺍﺷﻜﺎﻝ ﺍﺳﺖ ',
            '80' => 'ﺩﺭﺧﻮﺍﺳﺖ ﺗﺮﺍﻛﻨﺶ ﺭﺩ ﺷﺪﻩ ﺍﺳﺖ ',
            '81' => 'ﻛﺎﺭﺕ ﭘﺬﻳﺮﻓﺘﻪ ﻧﺸﺪ ( ﺍﺧﺘﺼﺎﺻﻲ ( SLM ',
            '82' => 'ﭘﻴﺎﻡ ﺗﺎﻳﻴﺪ ﺍﺯ ﺩﺳﺘﮕﺎﻩ ﺧﻮﺩ ﭘﺮﺩﺍﺯ ﺩﺭﻳﺎﻓﺖ ﻧﺸﺪﻩ ﺍﺳﺖ ( ﺍﺧﺘﺼﺎﺻﻲ ( SLm ',
            '83' => 'ﺳﺮﻭﻳﺲ ﮔﺮ ﺳﻮﺋﻴﭻ ﻛﺎﺭﺕ ﺗﺮﺍﻛﻨﺶ ﺭﺍ ﻧﭙﺬﻳﺮﻓﺘﻪ ﺍﺳﺖ ',
            '84' => 'ﺩﺭ ﺗﺮﺍﻛﻨﺸﻬﺎﻳﻲ ﻛﻪ ﺍﻧﺠﺎﻡ ﺁﻥ ﻣﺴﺘﻠﺰﻡ ﺍﺭﺗﺒﺎﻁ ﺑﺎ ﺻﺎﺩﺭ ﻛﻨﻨﺪﻩ ﺍﺳﺖ ﺩﺭ ﺻﻮﺭﺕ ﻓﻌﺎﻝ ﻧﺒﻮﺩﻥ ﺻﺎﺩﺭ ﻛﻨﻨﺪﻩ ﺍﻳﻦ ﭘﻴﺎﻡ ﺩﺭ ﭘﺎﺳﺦ ﺍﺭﺳﺎﻝ ﺧﻮﺍﻫﺪ ﺷﺪ ',
            '85' => 'ﭘﺮﺩﺍﺯﺵ ﮔﺮ ﻭ ﻳﺎ ﻣﺒﺪﺍ ﺍﻧﺠﺎﻡ ﺗﺮﺍﻛﻨﺶ ﻣﻌﺘﺒﺮ ﻧﻴﺴﺖ ',
            '86' => 'ﺗﺮﺍﻛﻨﺶ ﺩﺭﺧﻮﺍﺳﺘﻲ ﺑﺮﺍﻱ ﺑﺨﺶ ﺳﺨﺖ ﺍﻓﺰﺍﺭﻱ ﺩﺭﺧﻮﺍﺳﺖ ﺷﺪﻩ ﺍﺯ ﺁﻥ ﻗﺎﺑﻞ ﻗﺒﻮﻝ ﻧﻴﺴﺖ ',
            '87' => 'ﺳﻴﺴﺘﻢ ﺩﺭ ﺗﺒﺎﺩﻝ ﻛﻠﻴﺪ ﺭﻣﺰ ﺩﭼﺎﺭ ﻣﺸﻜﻞ ﺷﺪﻩ ﺍﺳﺖ ( ﻛﺪ ﭘﺎﺳﺦ ﺍﺧﺘﺼﺎﺻﻲ ( SLM ',
            '88' => 'ﺳﻴﺴﺘﻢ ﺩﺭ ﺗﺒﺎﺩﻝ ﻛﻠﻴﺪ MAC ﺩﭼﺎﺭ ﻣﺸﻜﻞ ﺷﺪﻩ ﺍﺳﺖ (ﻛﺪ ﭘﺎﺳﺦ ﺍﺧﺘﺼﺎﺻﻲ ( SLM ',
            '89' => 'ﻋﺪﻡ ﺗﺎﻳﻴﺪ ﺗﺮﺍﻛﻨﺶ ﺗﻮﺳﻂ ﺳﻮﺋﻴﭻ ﺧﺎﺭﺟﻲ ( ﻛﺪ ﭘﺎﺳﺦ ﺍﺧﺘﺼﺎﺻﻲ ( SLM ',
            '90' => 'ﺳﺎﻣﺎﻧﻪ ﻣﻘﺼﺪ ﺗﺮﺍﻛﻨﺶ ﺩﺭﺣﺎﻝ ﺍﻧﺠﺎﻡ ﻋﻤﻠﻴﺎﺕ ﭘﺎﻳﺎﻥ ﺭﻭﺯ ﻣﻲ ﺑﺎﺷﺪ ',
            '91' => 'ﺳﻴﺴﺘﻢ ﺻﺪﻭﺭ ﻣﺠﻮﺯ ﺍﻧﺠﺎﻡ ﺗﺮﺍﻛﻨﺶ ﻣﻮﻗﺘﺎ ﻏﻴﺮ ﻓﻌﺎﻝ ﺍﺳﺖ ﻭ ﻳﺎ ﺯﻣﺎﻥ ﺗﻌﻴﻴﻦ ﺷﺪﻩ ﺑﺮﺍﻱ ﺻﺪﻭ ﻣﺠﻮﺯ ﺑﻪ ﭘﺎﻳﺎﻥ ﺭﺳﻴﺪﻩ ﺍﺳﺖ ',
            '92' => 'ﻣﻘﺼﺪ ﺗﺮﺍﻛﻨﺶ ﭘﻴﺪﺍ ﻧﺸﺪ ',
            '93' => 'ﺍﻣﻜﺎﻥ ﺗﻜﻤﻴﻞ ﺗﺮﺍﻛﻨﺶ ﻭﺟﻮﺩ ﻧﺪﺍﺭﺩ ',
            '94' => 'ﺍﺭﺳﺎﻝ ﺗﻜﺮﺍﺭﻱ ﺗﺮﺍﻛﻨﺶ ﺑﻮﺟﻮﺩ ﺁﻣﺪﻩ ﺍﺳﺖ ',
            '95' => 'ﺩﺭ ﻋﻤﻠﻴﺎﺕ ﻣﻐﺎﻳﺮﺕ ﮔﻴﺮﻱ ﺗﺮﻣﻴﻨﺎﻝ ﺍﺷﻜﺎﻝ ﺭﺥ ﺩﺍﺩﻩ ﺍﺳﺖ ',
            '96' => 'ﺍﺷﻜﺎﻝ ﺩﺭ ﻋﻤﻠﻜﺮﺩ ﺳﻴﺴﺘﻢ ',
            '97' => 'ﺗﺮﺍﻛﻨﺶ ﺍﺯ ﺳﻮﻱ ﺻﺎﺩﺭﻛﻨﻨﺪﻩ ﻛﺎﺭﺕ ﺭﺩ ﺷﺪﻩ ﺍﺳﺖ ',
            '99' => 'ﺧﻄﺎﻱ ﺻﺎﺩﺭ ﻛﻨﻨﺪﮔﻲ 	',
            '200' =>'ﺳﺎﻳﺮ ﺧﻄﺎﻫﺎﻱ ﻧﮕﺎﺷﺖ ﻧﺸﺪﻩ ﺳﺎﻣﺎﻧﻪ ﻫﺎﻱ ﺑﺎﻧﻜﻲ'
        ];

        return array_key_exists($statusCode, $messages) ? $messages[$statusCode] : $statusCode;
    }

    protected function getSuccessResponseStatusCode(): int
    {
        return  0;
    }

    protected function getPurchaseUrl(): string
    {
        return $this->getBaseRestServiceUrl().'mhipg/api/Payment/NormalSale';
    }

    protected function getPaymentUrl(): string
    {
        return $this->getBaseRestServiceUrl().'mhui/home/index/'.$this->getInvoice()->getTransactionId();
    }

    protected function getVerificationUrl(): string
    {
        return $this->getBaseRestServiceUrl().'mhipg/api/Payment/confirm';
    }

    private function getBaseRestServiceUrl(): string
    {
        return 'https://pna.shaparak.ir/';
    }

    private function getRequestHeaders(): array
    {
        return config('gateway_novin_simple.request_headers');
    }

    public function refund(): array
    {
        if (!empty(request('Status')) && strtoupper(request('Status')) !== 'OK') {
            throw new RefundFailedException($this->getStatusMessage(request('Status')));
        }

        $refundPaymentData = $this->getRefundPaymentData();
        $response = $this->callApi($this->getRefundPaymentsUrl(), $refundPaymentData);

        if ($response['Status'] == $this->getSuccessResponseStatusCode()) {

            $token=$response['Token'];
            $message=$response['Message'];
            return $this->getStatusMessage($this->getSuccessResponseStatusCode());
        }

        throw new RefundFailedException($this->getStatusMessage($response['Status']));
    }

    protected function getRefundPaymentData(): array
    {
        return [
            'CorporationPin' => $this->settings['pin_code'],
            'Token' => request('token', $this->getInvoice()->getToken()),
        ];
    }

    protected function getRefundPaymentsUrl(): string
    {
        return $this->getBaseRestServiceUrl().'mhipg/api/Payment/Reverse';
//        return 'https://pna.shaparak.ir/mhipg/api/Payment/Reverse';
    }
}
