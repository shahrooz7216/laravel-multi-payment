<?php

return [

    /**
     * Important Note: Saman gateway payment uses invoice uuid for purchase & verification.
     * Save uuid in database for further use. Invoice transaction id will be set automatically
     * after successful payment verification.
     * 
     * Note: Merchant id is the same as Terminal id.
     */

    /**
     *  driver class namespace
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Saman::class,

    /**
     *  default configuration key name
     */
    'default_config' => 'default',

    /**
     *  gateway configurations (add as many as you want)
     */
    'default' => [
        'terminalId' => '',
        'callbackUrl' => 'https://yoursite.com/path/to',
        'description' => 'payment using saman',
    ],
    'other' => [
        'terminalId' => '',
        'callbackUrl' => 'https://yoursite.com/path/to',
        'description' => 'payment using saman',
    ]
];
