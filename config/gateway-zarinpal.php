<?php

return [
    'driver' => Omalizadeh\MultiPayment\Drivers\Zarinpal::class,
    'default' => [
            'mode'        => 'normal',
            // supported values: normal, sandbox, zaringate
            'merchantId'  => '',
            'callbackUrl' => 'https://yoursite.com/path/to',
            'description' => 'payment using zarinpal',
    ],
    'fitamin' => [
        'mode'        => 'normal',
        // supported values: normal, sandbox, zaringate
        'merchantId'  => '',
        'callbackUrl' => 'https://yoursite.com/path/to',
        'description' => 'payment using zarinpal',
    ]
];