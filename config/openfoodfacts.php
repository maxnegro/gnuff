<?php

return [

    'base_url' => env(
        'OPENFOODFACTS_BASE_URL',
        env('APP_ENV') === 'production'
            ? 'https://world.openfoodfacts.org/api/v3.6'
            : 'https://world.openfoodfacts.net/api/v2'
    ),

    'user_agent' => env('OPENFOODFACTS_USER_AGENT', 'Gnuff/1.0 (noreply@example.com)'),

    'request_timeout_seconds' => env('OPENFOODFACTS_REQUEST_TIMEOUT_SECONDS', 5),

    'retries' => env('OPENFOODFACTS_RETRIES', 1),

    'retry_delay_milliseconds' => env('OPENFOODFACTS_RETRY_DELAY_MILLISECONDS', 500),

    'product_lookup_limit_per_minute' => env('OPENFOODFACTS_PRODUCT_LOOKUP_LIMIT_PER_MINUTE', 15),

    'search_limit_per_minute' => env('OPENFOODFACTS_SEARCH_LIMIT_PER_MINUTE', 10),

    'server_id' => env('OPENFOODFACTS_SERVER_ID', 'default'),

    'circuit_breaker_seconds' => env('OPENFOODFACTS_CIRCUIT_BREAKER_SECONDS', 300),

    'image_timeout_seconds' => env('OPENFOODFACTS_IMAGE_TIMEOUT_SECONDS', 10),

    'cache' => [
        'product_found_ttl' => env('OPENFOODFACTS_PRODUCT_FOUND_TTL', 86400),
        'product_not_found_ttl' => env('OPENFOODFACTS_PRODUCT_NOT_FOUND_TTL', 86400),
        'product_error_ttl' => env('OPENFOODFACTS_PRODUCT_ERROR_TTL', 600),
        'search_ttl' => env('OPENFOODFACTS_SEARCH_TTL', 3600),
    ],

];
