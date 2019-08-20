<?php

return [

    'stack' => [
        'name' => getenv('OPG_LPA_STACK_NAME') ?: 'local',
        'environment' => getenv('OPG_LPA_STACK_ENVIRONMENT') ?: 'dev',
    ],

    'notify' => [
        'api' => [
            'key' => getenv('OPG_LPA_API_NOTIFY_API_KEY') ?: null,
        ],
    ],


    'admin' => [
        'dynamodb' => [
            'client' => [
                'endpoint' => getenv('OPG_LPA_COMMON_DYNAMODB_ENDPOINT') ?: null,
                'version' => '2012-08-10',
                'region' => 'eu-west-1',
                'credentials' => ( getenv('AWS_ACCESS_KEY_ID') && getenv('AWS_SECRET_ACCESS_KEY') ) ? [
                    'key'    => getenv('AWS_ACCESS_KEY_ID'),
                    'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
                ] : null,
            ],
            'settings' => [
                'table_name' => getenv('OPG_LPA_COMMON_ADMIN_DYNAMODB_TABLE') ?: 'lpa-properties-shared',
            ],
        ],

        'accounts' => getenv('OPG_LPA_COMMON_ADMIN_ACCOUNTS') ? explode(',', getenv('OPG_LPA_COMMON_ADMIN_ACCOUNTS')) : [],

        'account_cleanup_notification_recipients' => getenv('OPG_LPA_COMMON_ACCOUNT_CLEANUP_NOTIFICATION_RECIPIENTS') ? explode(',', getenv('OPG_LPA_COMMON_ACCOUNT_CLEANUP_NOTIFICATION_RECIPIENTS')) : [],
    ],

    'db' => [
        'postgres' => [
            'default' => [
                'adapter' => 'pgsql',
                'host'      => getenv('OPG_LPA_POSTGRES_HOSTNAME') ?: null,
                'port'      => getenv('OPG_LPA_POSTGRES_PORT') ?: null,
                'dbname'    => getenv('OPG_LPA_POSTGRES_NAME') ?: null,
                'username'  => getenv('OPG_LPA_POSTGRES_USERNAME') ?: null,
                'password'  => getenv('OPG_LPA_POSTGRES_PASSWORD') ?: null,
            ],
        ],
    ],

    'pdf' => [

        'cache' => [

            's3' => [
                'settings' => [
                    'Bucket' => getenv('OPG_LPA_COMMON_PDF_CACHE_S3_BUCKET') ?: null,
                ],
                'client' => [
                    'endpoint' => getenv('OPG_LPA_COMMON_S3_ENDPOINT') ?: null,
                    'use_path_style_endpoint' => true,
                    'version' => '2006-03-01',
                    'region' => 'eu-west-1',
                ],
            ], // S3

        ], // cache

        'queue' => [
            'sqs' => [
                'settings' => [
                    'url' => getenv('OPG_LPA_COMMON_PDF_QUEUE_URL') ?: null,
                ],
                'client' => [
                    'region' => 'eu-west-1',
                    'version' => '2012-11-05',
                ],
            ],
        ],

    ], // pdf


    'cron' => [
        'lock' => [
            'dynamodb' => [
                'client' => [
                    'endpoint' => getenv('OPG_LPA_COMMON_DYNAMODB_ENDPOINT') ?: null,
                    'version' => '2012-08-10',
                    'region' => 'eu-west-1',
                    'credentials' => ( getenv('AWS_ACCESS_KEY_ID') && getenv('AWS_SECRET_ACCESS_KEY') ) ? [
                        'key'    => getenv('AWS_ACCESS_KEY_ID'),
                        'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
                    ] : null,
                ],
                'settings' => [
                    'table_name' => getenv('OPG_LPA_COMMON_CRONLOCK_DYNAMODB_TABLE') ?: 'lpa-locks-shared',
                ],
            ],
        ], // lock
    ], // cron


    'session' => [
        'dynamodb' => [
            'client' => [
                'endpoint' => getenv('OPG_LPA_COMMON_DYNAMODB_ENDPOINT') ?: null,
                'version' => '2012-08-10',
                'region' => 'eu-west-1',
                'credentials' => ( getenv('AWS_ACCESS_KEY_ID') && getenv('AWS_SECRET_ACCESS_KEY') ) ? [
                    'key'    => getenv('AWS_ACCESS_KEY_ID'),
                    'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
                ] : null,
            ],
            'settings' => [
                'table_name' => getenv('OPG_LPA_COMMON_SESSION_DYNAMODB_TABLE') ?: 'lpa-sessions-shared',
                // Whether Time To Live is enabled on the sesson table
                'ttl_enabled' => getenv('OPG_LPA_COMMON_SESSION_DYNAMODB_TTL_ENABLED') ?: true,
                // The DB field to use for the Time To Live expiry time
                'ttl_attribute' => getenv('OPG_LPA_COMMON_SESSION_DYNAMODB_TTL_ATTRIBUTE') ?: 'expires',
                'batch_config' => [
                    // Sleep before each flush to rate limit the garbage collection.
                    'before' => function(){ sleep(1); },
                ]
            ],
        ],
    ], // session

    'processing-status' => [
        'endpoint' => getenv('OPG_LPA_PROCESSING_STATUS_ENDPOINT') ?: 'http://gateway:5000/v1/lpa-online-tool/lpas/'
    ]
];
