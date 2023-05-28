<?php

return [

    /**
     *  driver class namespace
     */
    'driver' => shahrooz7216\MultiPayment\Drivers\Zibal\Zibal::class,

    /**
     *  gateway configurations
     */
    'main' => [
        'merchant'  => '', // use 'zibal' for sandbox (testing) mode
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using zarinpal',
    ],
    'other' => [
        'merchant'  => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using zarinpal',
    ]
];
