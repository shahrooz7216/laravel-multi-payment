<?php

return [

    /**
     *  driver class namespace
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Parsian\Parsian::class,

    /**
     *  soap client options
     */
    'soap_options' => [
        'encoding' => 'UTF-8',
    ],

    /**
     *  gateway configurations
     */
    'behandam' => [
        'terminal_id' => '',
        'pin_code' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'callback_method' => 'POST', // Supported values: POST, GET
        'description' => 'رژیم دکتر کرمانی',
    ],

    'other' => [
        'merchant'  => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using parsian',
    ]
];
