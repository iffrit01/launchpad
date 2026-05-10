<?php

return [
    [
        'slug' => 'qa-fling',
        'name' => 'QA Fling',
        'deployer_file' => 'deployments/qa-fling.php',
        'source_update_task' => 'deploy:update-cache-qa',
        'stage' => 'production',
        'active' => true,
        'release_branch_patterns' => [
            'release-2026*',
            'master',
            'feature/*',
            'bugfix/*',
            'hotfix/*',
            'chore/*',
            'refactor/*',
            'docs/*',
            'test/*',
            'experiment/*',
        ],
        'environments' => [
            [
                'name' => 'staging',
                'label' => 'Staging',
                'selector' => 'staging.fling.com',
            ],
            [
                'name' => 'production',
                'label' => 'Production',
                'selector' => 'www2-ord.fling.com',
            ],
        ],
    ],
];
