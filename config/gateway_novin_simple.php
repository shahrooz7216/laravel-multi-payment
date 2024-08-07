<?php

return [

    /**
     *  driver class namespace
     */
    'driver' => shahrooz7216\MultiPayment\Drivers\NovinSimple\NovinSimple::class,

    /**
     *  soap client options
     */
    'soap_options' => [
        'encoding' => 'UTF-8',
    ],

    /**
     *  gateway configurations
     */
    'main' => [
        'pin_code' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'callback_method' => 'POST',
        'description' => '',
    ],

    'other' => [
        'pin_code'  => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using parsian',
    ]
];
