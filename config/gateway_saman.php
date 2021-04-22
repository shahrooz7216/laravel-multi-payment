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
    'driver' => Omalizadeh\MultiPayment\Drivers\Saman::class,

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
