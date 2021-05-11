<?php

return [

    /**
     *  driver class namespace
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Pasargad\Pasargad::class,

    /**
     *  Headers added to rest api calls
     */
    'request_headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ],

    /**
     *  gateway configurations
     */
    'default' => [
        'merchant_code'  => '',
        'terminal_code' => '',
        'certificate_type' => 'xml_file', // Supported values: xml_file, xml_string
        'certificate' => '', // Certificate as a string or certificate.xml file path
        'callback_url' => 'https://yoursite.com/path/to',
    ]
];
