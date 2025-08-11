<?php
return [
    'ksp' => [
        'url' => env('KSP_API_URL', 'https://layanan-api.ksp.go.id/index.php/login'),
        'key' => env('KSP_API_KEY', 'e7f0s9Cc9feBf61d49i3Kz5'),
        'timeout' => env('KSP_API_TIMEOUT', 30),
        'verify_ssl' => env('KSP_API_VERIFY_SSL', false),
    ],
];