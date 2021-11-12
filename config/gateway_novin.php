<?php

return [

    /**
     *  driver class namespace
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Novin\Novin::class,

    /**
     *  Gateway payment page language
     *  Supported values by gateway: fa, en
     */
    'language' => 'fa',

    /**
     *  gateway configurations
     */
    'main' => [
        'username' => '',
        'password' => '',
        'certificate_path' => '', // Certificate file path as string
        'certificate_password' => '',
        'temp_files_dir' => '', // Temp text files dir path, example: storage_path('novin/')
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using eghtesade novin',
    ],
    'other' => [
        'username' => '',
        'password' => '',
        'certificate_path' => '',
        'certificate_password' => '',
        'temp_files_dir' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using eghtesade novin',
    ]
];
