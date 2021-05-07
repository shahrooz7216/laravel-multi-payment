<?php

return [

    /**
     * Important Note: Saman gateway payment uses RefNum for verification.
     * RefNum will be set as invoice transaction_id after successful payment verification
     * and it must be saved into database for further use.
     * 
     * Note: Merchant id is the same as Terminal id.
     */

    /**
     *  driver class namespace
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Saman\Saman::class,

    /**
     *  Headers added to rest api calls
     */
    'request_headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ],

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
        'terminal_id' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'callback_method' => 'POST', // Supported values: POST, GET
        'description' => 'payment using saman',
    ],
    'other' => [
        'terminal_id' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'callback_method' => 'POST',
        'description' => 'payment using saman',
    ]
];
