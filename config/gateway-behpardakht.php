<?php

return [
    
    /**
     *  driver class namespace
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Behpardakht::class,

    /**
     *  default configuration key name
     */
    'default_config' => 'default',

    /**
     *  gateway configurations (add as many as you want)
     */
    'default' => [
        'terminalId' => '',
        'username' => '',
        'password' => '',
        'callbackUrl' => 'http://yoursite.com/path/to',
        'description' => 'payment using behpardakht',
    ],
    'other' => [
        'terminalId' => '',
        'username' => '',
        'password' => '',
        'callbackUrl' => 'http://yoursite.com/path/to',
        'description' => 'payment using behpardakht',
    ]
];
