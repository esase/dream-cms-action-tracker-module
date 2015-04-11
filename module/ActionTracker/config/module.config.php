<?php

return [
    'controllers' => [
        'invokables' => [
            'actions-tracker-administration' => 'ActionTracker\Controller\ActionTrackerAdministrationController'
        ]
    ],
    'router' => [
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type'     => 'getText',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
                'text_domain'  => 'default'
            ]
        ]
    ],
    'view_helpers' => [
        'invokables' => [
        ]
    ]
];