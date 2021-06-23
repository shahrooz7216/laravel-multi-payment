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
        'certificate_path' => '', // Certificate file path as string
        'certificate_password' => '',
        'temp_files_dir' => '', // Path to text files (unsigned & signed data)
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using eghtesade novin',
    ]
];
