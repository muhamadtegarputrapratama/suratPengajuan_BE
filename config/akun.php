<?php

return [
    'kpap' => [
        'email' => env('ADMIN_KPAP_EMAIL'),
        'password' => env('ADMIN_KPAP_PASSWORD'),
    ],

    // Satu akun per departemen. Email dasar diberi "+kode" tiap departemen:
    // admindept@gmail.com -> admindept+mnh@gmail.com
    'departemen' => [
        'email' => env('ADMIN_DEPARTEMEN_EMAIL'),
        'password' => env('ADMIN_DEPARTEMEN_PASSWORD'),
    ],
];