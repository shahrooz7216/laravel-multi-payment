<?php

return [

    /**
     *  driver class namespace
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Novin\Novin::class,

    /**
     *  Headers added to rest api calls
     */
    'request_headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ],

    /**
     *  Cache key for storing session_id provided by gateway
     */
    'session_id_cache_key' => 'novin_gateway_session_id',

    /**
     *  Gateway payment page language
     *  Supported values by gateway: fa, en
     */
    'language' => 'fa',

    /**
     *  gateway configurations
     */
    'default' => [
        'username' => '',
        'password' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using eghtesade novin',
    ]
];
