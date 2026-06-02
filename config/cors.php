<?php

$extraOrigins = array_filter(array_map(
    static fn (string $origin) => rtrim(trim($origin), '/'),
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
));

$localDevOrigins = env('APP_ENV') === 'local'
    ? [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ]
    : [];

$frontendUrl = env('FRONTEND_URL');
if (is_string($frontendUrl) && trim($frontendUrl) !== '') {
    $extraOrigins[] = rtrim(trim($frontendUrl), '/');
}

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_unique(array_filter(array_merge(
        [
            'https://staging.repronig.org',
            'https://app.repronig.org',
        ],
        $localDevOrigins,
        $extraOrigins,
    )))),
    'allowed_origins_patterns' => env('APP_ENV') === 'local'
        ? ['#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#']
        : [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Content-Disposition'],
    'max_age' => 0,
    'supports_credentials' => false,
];
