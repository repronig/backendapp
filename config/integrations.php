<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WIPO Connect outbound delivery
    |--------------------------------------------------------------------------
    |
    | WIPO Connect endpoints differ by deployment and programme. Use encrypted
    | integration config (api_base_url, sync_path or sync_url, auth) and set
    | WIPO_CONNECT_DELIVERY=http when your environment is allowed to call out.
    |
    | Values: "stub" (no HTTP), "http" (POST/GET to configured URL with auth).
    |
    */
    'wipo_connect' => [
        'delivery' => env('WIPO_CONNECT_DELIVERY', 'stub'),
        'http_timeout_seconds' => (int) env('WIPO_CONNECT_HTTP_TIMEOUT', 30),
        'webhook_require_hmac' => filter_var(env('WIPO_CONNECT_WEBHOOK_REQUIRE_HMAC', false), FILTER_VALIDATE_BOOL),
        'webhook_allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('WIPO_CONNECT_WEBHOOK_ALLOWED_IPS', ''))))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration outbox (all providers)
    |--------------------------------------------------------------------------
    |
    | Failed deliveries are retried with exponential backoff until max_attempts
    | is reached. Delay is capped at retry_backoff_max_seconds.
    |
    */
    'outbox' => [
        'max_attempts' => max(1, (int) env('INTEGRATION_OUTBOX_MAX_ATTEMPTS', 5)),
        'retry_backoff_base_seconds' => max(1, (int) env('INTEGRATION_OUTBOX_RETRY_BACKOFF_BASE', 60)),
        'retry_backoff_max_seconds' => max(1, (int) env('INTEGRATION_OUTBOX_RETRY_BACKOFF_MAX', 3600)),
        'health_failed_window_hours' => max(1, (int) env('INTEGRATION_OUTBOX_HEALTH_FAILED_WINDOW_HOURS', 24)),
        'health_failed_threshold' => max(1, (int) env('INTEGRATION_OUTBOX_HEALTH_FAILED_THRESHOLD', 25)),
    ],

];
