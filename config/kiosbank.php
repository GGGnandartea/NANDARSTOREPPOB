<?php

return [
    'profiles' => [
        'development' => [
            'verify_tls' => env('KIOSBANK_DEVELOPMENT_VERIFY_TLS', true),
            'ca_bundle' => env('KIOSBANK_DEVELOPMENT_CA_BUNDLE'),
            'username' => env('KIOSBANK_DEVELOPMENT_USERNAME', ''),
            'password' => env('KIOSBANK_DEVELOPMENT_PASSWORD', ''),
        ],
        'production' => [
            'ca_bundle' => env('KIOSBANK_PRODUCTION_CA_BUNDLE'),
            'username' => env('KIOSBANK_PRODUCTION_USERNAME', ''),
            'password' => env('KIOSBANK_PRODUCTION_PASSWORD', ''),
        ],
    ],
    'mitra' => env('KIOSBANK_MITRA', 'DJI'),
    'merchant_id' => env('KIOSBANK_MERCHANT_ID', ''),
    'account_id' => env('KIOSBANK_ACCOUNT_ID', ''),
    'counter_id' => env('KIOSBANK_COUNTER_ID', '1'),
    'merchant_name' => env('KIOSBANK_MERCHANT_NAME', 'PREMIUMKITA'),
];
