<?php

return [
    // Long-lived first-party cookie that identifies a browser (no personal data in it).
    'cookie' => env('VISITORS_COOKIE', 'dc_vid'),
    'cookie_days' => (int) env('VISITORS_COOKIE_DAYS', 365),

    // A visitor counts as online if seen within this many minutes.
    'online_minutes' => (int) env('VISITORS_ONLINE_MINUTES', 5),

    // Rows not seen for this many days are deleted by `php artisan model:prune` (scheduled daily).
    'retention_days' => (int) env('VISITORS_RETENTION_DAYS', 90),

    // Requests that are never tracked.
    'ignore_paths' => ['livewire/*', 'up', 'storage/*', 'build/*', '*/ai-plan/*/status'],
    'ignore_user_agents' => '/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python-requests|headless|lighthouse/i',
];
