<?php

return [
    // Your project's API key from the dashboard (wsy_live_… or wsy_test_…). Keep it in .env.
    'api_key' => env('WATSY_KEY'),

    // The signing secret of your webhook endpoint (dashboard → Webhooks).
    'webhook_secret' => env('WATSY_WEBHOOK_SECRET'),

    'base_url' => env('WATSY_BASE_URL', 'https://watsy.me'),

    // Seconds to wait for the API.
    'timeout' => 15,
];
