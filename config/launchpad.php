<?php

return [
    'webhook_token' => env('LAUNCHPAD_WEBHOOK_TOKEN'),
    'source_root' => env('LAUNCHPAD_SOURCE_ROOT', '/sources'),
    'log_root' => env('LAUNCHPAD_LOG_ROOT', '/deploy-logs'),
    'deployer_username' => env('LAUNCHPAD_DEPLOYER_USERNAME', 'web1'),
];
