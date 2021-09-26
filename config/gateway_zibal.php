<?php

return [

    /**
     *  driver class namespace
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Zibal\Zibal::class,

    /**
     *  rest api call headers
     */
    'request_headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ],

    /**
     *  gateway configurations
     */
    'main' => [
        'merchant'  => '', // Use 'zibal' for sandbox (testing) mode
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using zarinpal',
    ],
    'other' => [
        'merchant'  => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using zarinpal',
    ]
];
