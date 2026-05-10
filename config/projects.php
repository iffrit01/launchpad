<?php

return [
    [
        'slug' => 'qa-fling',
        'name' => 'QA Fling',
        'deployer_file' => 'deployments/qa-fling.php',
        'source_update_task' => 'deploy:update-cache-qa',
        'stage' => 'production',
        'active' => true,
    ],
];
