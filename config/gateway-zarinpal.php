<?php

return [
    
    /**
     *  driver class namespace
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Zarinpal::class,

    /**
     *  default configuration key name
     */
    'default_config' => 'default',

    /**
     *  gateway configurations (add as many as you want)
     */
    'default' => [
        'mode'        => 'normal', // supported values: normal, sandbox, zaringate
        'merchantId'  => '',
        'callbackUrl' => 'https://yoursite.com/path/to',
        'description' => 'payment using zarinpal',
    ],
    'other' => [
        'mode'        => 'sandbox', // supported values: normal, sandbox, zaringate
        'merchantId'  => '',
        'callbackUrl' => 'https://yoursite.com/path/to',
        'description' => 'payment using zarinpal',
    ]
];
