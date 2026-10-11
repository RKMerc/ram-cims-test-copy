<?php

return [
    /*
    | The key stays on the server. Leave it empty to keep RAMsey on local rules.
    | Do not enable live clinic conversations until the school has approved the
    | provider's retention, processing, and cross-border terms.
    */
    'api_key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    'timeout' => (int) env('GEMINI_TIMEOUT', 8),
    'rate_limit' => (int) env('GEMINI_RATE_LIMIT', 12),
];
