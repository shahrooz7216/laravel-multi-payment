<?php

return [
    'default_driver' => 'zarinpal',
    'default_app' => 'my_app_name',

    'convert_to_rials' => true, // set to false if your in-app currency is IRR

    'my_app_name' => [
        'behpardakht' => [],
        'sep' => [],
        'zarinpal' => [
            /* normal api */
            'purchaseApiUrl' => 'https://ir.zarinpal.com/pg/services/WebGate/wsdl',
            'paymentApiUrl' => 'https://zarinpal.com/pg/StartPay/',
            'verificationApiUrl' => 'https://ir.zarinpal.com/pg/services/WebGate/wsdl',

            /* sandbox api */
            'sandboxPurchaseApiUrl' => 'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl',
            'sandboxPaymentApiUrl' => 'https://sandbox.zarinpal.com/pg/StartPay/',
            'sandboxVerificationApiUrl' => 'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl',

            /* zarinGate api */
            'zaringatePurchaseApiUrl' => 'https://ir.zarinpal.com/pg/services/WebGate/wsdl',
            'zaringatePaymentApiUrl' => 'https://zarinpal.com/pg/StartPay/:authority/ZarinGate',
            'zaringateVerificationApiUrl' => 'https://ir.zarinpal.com/pg/services/WebGate/wsdl',

            'mode' => 'normal', // supported values: normal, sandbox, zaringate
            'merchantId' => '',
            'callbackUrl' => 'https://yoursite.com/path/to',
            'description' => 'payment using zarinpal',
        ]
    ],
    'other_app_name' => [
        'behpardakht' => [],
        'sep' => [],
        'zarinpal' => []
    ],

    'aliases' => [
        'zarinpal' => Omalizadeh\MultiPayment\Drivers\Zarinpal::class
    ]
];
