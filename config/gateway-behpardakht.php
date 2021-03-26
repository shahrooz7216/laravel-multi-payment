<?php

return [

    /**
     *  driver class namespace
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Behpardakht::class,

    /**
     *  soap client options
     */
    'soap_options' => [],

    /**
     *  gateway configurations (add as many as you want)
     */
    'default' => [
        'terminal_id' => '',
        'username' => '',
        'password' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using behpardakht',
    ],
    'other' => [
        'terminal_id' => '',
        'username' => '',
        'password' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using behpardakht',
    ]
];
