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
        'merchant_id'  => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'mode'        => 'normal', // Supported values: normal, sandbox, zaringate
        'description' => 'payment using zarinpal',
    ],
    'other' => [
        'merchant_id'  => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'mode'        => 'sandbox',
        'description' => 'payment using zarinpal',
    ]
];
