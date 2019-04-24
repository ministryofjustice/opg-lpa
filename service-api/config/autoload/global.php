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

    'log' => [
        'sns' => [
            'endpoints' => [
                'major' => getenv('OPG_LPA_COMMON_LOGGING_SNS_ENDPOINTS_MAJOR') ?: null,
                'minor' => getenv('OPG_LPA_COMMON_LOGGING_SNS_ENDPOINTS_MINOR') ?: null,
                'info' => getenv('OPG_LPA_COMMON_LOGGING_SNS_ENDPOINTS_INFO') ?: null,
            ],
            'client' => [
                'version' => '2010-03-31',
                'region' => 'eu-west-1',
            ],
        ], // sns
    ], // log

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
            'auto_create' => getenv('OPG_LPA_COMMON_DYNAMODB_AUTO_CREATE') ?: false,
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

        'encryption' => [
            // Keys MUST be a 32 character ASCII string
            'keys' => [
                'document' => getenv('OPG_LPA_API_PDF_ENCRYPTION_KEY_DOCUMENT') ?: null, // Key for generated PDFs in the file store
            ],
            'options' => [
                'algorithm' => 'aes',
                'mode' => 'cbc',
            ],
        ],

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

        'DynamoQueue' => [
            'settings' => [
                'table_name' => getenv('OPG_LPA_COMMON_QUEUE_DYNAMODB_TABLE') ?: 'lpa-pdf-queue-shared',
            ],
            'client' => [
                'endpoint' => getenv('OPG_LPA_COMMON_DYNAMODB_ENDPOINT') ?: null,
                'version' => '2012-08-10',
                'region' => 'eu-west-1',
            ],
        ], // DynamoQueue

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
                'auto_create' => getenv('OPG_LPA_COMMON_DYNAMODB_AUTO_CREATE') ?: false,
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
            'auto_create' => getenv('OPG_LPA_COMMON_DYNAMODB_AUTO_CREATE') ?: false,
        ],
    ], // session

    'processing-status' => [
        'track-from-date' => getenv('OPG_LPA_TRACK_FROM_DATE')?: '2019-02-13',
        'endpoint' => getenv('OPG_LPA_PROCESSING_STATUS_ENDPOINT') ?: 'http://gateway:5000/v1/lpa-online-tool/lpas/'
    ]
];
