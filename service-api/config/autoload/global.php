<?php

return [

    'stack' => [
        'name' => getenv('OPG_LPA_STACK_NAME') ?: 'local',
        'environment' => getenv('OPG_LPA_STACK_ENVIRONMENT') ?: 'dev',
    ],

    //  TODO - To be removed when account clean up is refactored to remove API self call
    'cleanup' => [
        'notification' => [
            'callback' => getenv('OPG_LPA_COMMON_ACCOUNT_CLEANUP_NOTIFICATION_ENDPOINT') ?: null,
            'token' => getenv('OPG_LPA_COMMON_ACCOUNT_CLEANUP_NOTIFICATION_TOKEN') ?: null,
        ],
        'api-target' => getenv('OPG_LPA_AUTH_API_CLEANUP_TARGET') ?: null,
        'api-token' => getenv('OPG_LPA_COMMON_AUTH_CLEANUP_TOKEN') ?: null,
    ], // cleanup

    //  TODO - To be removed when API self call is removed
    'authentication' => [
        'endpoint' => getenv('OPG_LPA_API_ENDPOINTS_AUTH') ?: 'https://apiv2/v1/authenticate',
        'clean-up-token' => getenv('OPG_LPA_COMMON_AUTH_CLEANUP_TOKEN'),
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
                'credentials' => ( getenv('AWS_ACCESS_KEY_ID') && getenv('AWS_SECRET_ACCESS_KEY') ) ? [
                    'key'    => getenv('AWS_ACCESS_KEY_ID'),
                    'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
                ] : null,
            ],
        ], // sns
    ], // log

    'admin' => [
        'accounts' => getenv('OPG_LPA_COMMON_ADMIN_ACCOUNTS') ? explode(',', getenv('OPG_LPA_COMMON_ADMIN_ACCOUNTS')) : [],
    ],

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

    'db' => [
        'mongo' => [
            'default' => [
                'hosts' => [
                    'mongodb-01:27017',
                    'mongodb-02:27017',
                    'mongodb-03:27017'
                ],
                'options' => [
                    'db' => 'opglpa-api',
                    'username' => 'opglpa-api',
                    'replicaSet' => 'rs0',
                    'connect' => false,
                    'connectTimeoutMS' => 1000,
                    'socketTimeoutMS' => 600000,
                    'w' => 'majority',
                    'ssl' => true,
                    'password' => getenv('OPG_LPA_API_MONGODB_PASSWORD') ?: null,
                ],
                'driverOptions' => [
                    'weak_cert_validation' => true //Allows usage of self signed certificates
                ],
            ],
            'auth' => [
                'hosts' => [
                    'mongodb-01:27017',
                    'mongodb-02:27017',
                    'mongodb-03:27017'
                ],
                'options' => [
                    'db' => 'opglpa-auth',
                    'username' => 'opglpa-auth',
                    'replicaSet' => 'rs0',
                    'connect' => false,
                    'connectTimeoutMS' => 1000,
                    'w' => 'majority',
                    'ssl' => true,
                    'password' => getenv('OPG_LPA_AUTH_MONGODB_PASSWORD') ?: null,
                ],
                'driverOptions' => [
                    'weak_cert_validation' => true //Allows usage of self signed certificates
                ],
            ],
        ], // mongo

        // Used to access generated PDFs.
        'redis' => [
            'default' => [
                'host' => getenv('OPG_LPA_COMMON_RESQUE_REDIS_HOST') ?: null,
                'port' => 6379,
            ],
        ], // redis

        // The queue for PDFs to be generated.
        'resque' => [

            'default' => [

                'host' => getenv('OPG_LPA_COMMON_RESQUE_REDIS_HOST') ?: null,
                'port' => 6379,

            ],

        ], // resque

    ],

    'pdf' => [

        'encryption' => [
            // Keys MUST be a 32 character ASCII string
            'keys' => [
                'queue' => getenv('OPG_LPA_API_PDF_ENCRYPTION_KEY_QUEUE') ?: null,       // Key for JSON pushed onto the queue
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
                    'version' => '2006-03-01',
                    'region' => 'eu-west-1',
                    'credentials' => ( getenv('AWS_ACCESS_KEY_ID') && getenv('AWS_SECRET_ACCESS_KEY') ) ? [
                        'key'    => getenv('AWS_ACCESS_KEY_ID'),
                        'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
                    ] : null,
                ],
            ], // S3

        ], // cache

        'DynamoQueue' => [
            'settings' => [
                'table_name' => getenv('OPG_LPA_COMMON_QUEUE_DYNAMODB_TABLE') ?: 'lpa-pdf-queue-shared',
            ],
            'client' => [
                'endpoint' => getenv('OPG_LPA_COMMON_DYNAMODB_ENDPOINT') ?: null,
                'version' => '2012-08-10',
                'region' => 'eu-west-1',
                'credentials' => ( getenv('AWS_ACCESS_KEY_ID') && getenv('AWS_SECRET_ACCESS_KEY') ) ? [
                    'key'    => getenv('AWS_ACCESS_KEY_ID'),
                    'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
                ] : null,
            ],
        ], // DynamoQueue

    ], // pdf

];
