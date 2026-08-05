<?php

return [
    'webhook_secret' => env('SETTLEMENT_WEBHOOK_SECRET', 'test-secret-key'),
    'webhook_url' => env('SETTLEMENT_WEBHOOK_URL', 'http://localhost:8000/api/webhook/settlement'),
];