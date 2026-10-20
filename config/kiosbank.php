<?php

return [
    'profiles' => [
        'development' => [
            'username' => env('KIOSBANK_DEVELOPMENT_USERNAME', ''),
            'password' => env('KIOSBANK_DEVELOPMENT_PASSWORD', ''),
        ],
        'production' => [
            'username' => env('KIOSBANK_PRODUCTION_USERNAME', ''),
            'password' => env('KIOSBANK_PRODUCTION_PASSWORD', ''),
        ],
    ],
    'mitra' => env('KIOSBANK_MITRA', 'DJI'),
    'merchant_id' => env('KIOSBANK_MERCHANT_ID', ''),
    'account_id' => env('KIOSBANK_ACCOUNT_ID', ''),
    'counter_id' => env('KIOSBANK_COUNTER_ID', '1'),
    'merchant_name' => env('KIOSBANK_MERCHANT_NAME', 'NANDARSTORE'),
];
