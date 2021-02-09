<?php

return [
    /**
     * add gateway from other config file
     */
    'default_driver' => env('DEFAULT_GATEWAY', 'zarinpal.fitamin'),
    'convert_to_rials' => true, // set to false if your in-app currency is IRR
];
