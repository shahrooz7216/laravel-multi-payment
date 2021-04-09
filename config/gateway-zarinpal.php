<?php

return [
    
    /**
     *  driver class namespace
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Zarinpal::class,

    /**
     *  soap client options
     */
    'soap_options' => [
        'encoding' => 'UTF-8'
    ],

    /**
     *  gateway configurations
     */
    'default' => [
        'mode'        => 'normal', // supported values: normal, sandbox, zaringate
        'merchant_id'  => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using zarinpal',
    ],
    'other' => [
        'mode'        => 'sandbox', // supported values: normal, sandbox, zaringate
        'merchant_id'  => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using zarinpal',
    ]
];
