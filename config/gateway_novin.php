<?php

return [

    /**
     *  driver class namespace
     */
    'driver' => shahrooz7216\MultiPayment\Drivers\Novin\Novin::class,

    /**
     *  gateway payment page language
     *  supported values: fa, en
     */
    'language' => 'fa',

    /**
     *  gateway configurations
     */
    'main' => [
        'username' => env('EGHTESAD_NOVIN_MID', ''),
        'password' => env('EGHTESAD_NOVIN_PASSWORD', ''),
        'certificate_path' => env('EGHTESAD_NOVIN_CERT_PATH', ''), // certificate file path as string
        'certificate_password' => env('EGHTESAD_NOVIN_CERT_PASSWORD', ''),
        'temp_files_dir' => storage_path('logs/novin/'), // temp text files dir path, example: storage_path('novin')
        'callback_url' => env('EGHTESAD_NOVIN_CALLBACK_URL', 'https://modema.com/payments/novin.php'),
        'description' => 'payment using eghtesad-e-novin',
        // Added By Shahrooz 1402-03-02:
        'terminal_id' => env('EGHTESAD_NOVIN_TERMINAL_ID', ''),
        'novin_token' => env('EGHTESAD_NOVIN_TOKEN',null),
        'mode' => env('EGHTESAD_NOVIN_MODE','NoSign'), // Pardakht Novin modes: Sign/NoSign
    ],
    'other' => [
        'username' => '',
        'password' => '',
        'certificate_path' => '',
        'certificate_password' => '',
        'temp_files_dir' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using eghtesad-e novin',

        // Added By Shahrooz 1402-03-02:
        'novin_token' => '',
        'mode' => '', // Pardakht Novin modes: Sign/NoSign
    ]
];
