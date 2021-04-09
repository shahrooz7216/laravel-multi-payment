<?php

return [

    /**
     * Important Note: Saman gateway payment uses invoice uuid for purchase & verification.
     * After successful purchase, uuid will be returned automatically. Save uuid in database
     * for verification. Invoice transaction id will be set after successful payment verification
     * and it must be saved into database manually for further use.
     * 
     * Note: Merchant id is the same as Terminal id.
     */

    /**
     *  driver class namespace
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Saman::class,

    /**
     *  api request headers sent to gateway
     */
    'request_headers' => [
        'Accept' => 'application/json'
    ],

    /**
     *  gateway configurations
     */
    'default' => [
        'terminal_id' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using saman',
    ],
    'other' => [
        'terminal_id' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using saman',
    ]
];
