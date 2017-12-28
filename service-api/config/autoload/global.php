<?php

return array(

    'stack' => [
        'name' => getenv('OPG_LPA_STACK_NAME') ?: 'local',
        'environment' => getenv('OPG_LPA_STACK_ENVIRONMENT') ?: 'dev',
    ],

    'authentication' => [
        'ping' => getenv('OPG_LPA_API_ENDPOINTS_AUTH_PING') ?: 'https://authv2/ping',
        'endpoint' => getenv('OPG_LPA_API_ENDPOINTS_AUTH') ?: 'https://authv2/v1/authenticate',
        'clean-up-token' => getenv('OPG_LPA_COMMON_AUTH_CLEANUP_TOKEN'),
    ],

    'cron' => [

        'lock' => [

            'dynamodb' => [
                'client' => [
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

                'hosts' => [ 'mongodb-01:27017', 'mongodb-02:27017', 'mongodb-03:27017' ],
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
                ]

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

    'log' => [

        'path' => getenv('OPG_LPA_COMMON_APPLICATION_LOG_PATH') ?: '/var/log/opg-lpa-api2/application.log',
        'sentry-uri' => getenv('OPG_LPA_COMMON_SENTRY_API_URI') ?: null,

    ], // log

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
                'version' => '2012-08-10',
                'region' => 'eu-west-1',
                'credentials' => ( getenv('AWS_ACCESS_KEY_ID') && getenv('AWS_SECRET_ACCESS_KEY') ) ? [
                    'key'    => getenv('AWS_ACCESS_KEY_ID'),
                    'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
                ] : null,
            ],
        ], // DynamoQueue

    ], // pdf

);
