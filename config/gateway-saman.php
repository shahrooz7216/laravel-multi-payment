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
     *  gateway configurations (add as many as you want)
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
