<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    // Explicit origins only (no '*'). Set CORS_ORIGINS (comma separated) or FRONTEND_URL in .env for production.
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', env(
        'CORS_ORIGINS',
        env('FRONTEND_URL', 'http://localhost:5500,http://127.0.0.1:5500,http://localhost:3000,http://127.0.0.1:3000,http://localhost:8080')
    ))))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false, // bearer tokens, no cookies
];
