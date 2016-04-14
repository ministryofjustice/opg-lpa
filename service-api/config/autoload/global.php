<?php

return array(

    'stack' => [
        'name' => getenv('OPG_LPA_STACK_NAME') ?: 'local',
        'environment' => getenv('OPG_LPA_STACK_ENVIRONMENT') ?: 'dev',
    ],

    'authentication' => [
        'endpoint' => getenv('OPG_LPA_API_ENDPOINTS_AUTH') ?: 'https://authv2/v1/authenticate',
    ],

    'cron' => [

        'lock' => [

            'dynamodb' => [
                'client' => [
                    'version' => '2012-08-10',
                    'region' => 'eu-west-1',
                    'credentials' => ( getenv('OPG_LPA_AWS_KEY') && getenv('OPG_LPA_AWS_SECRET') ) ? [
                        'key'    => getenv('OPG_LPA_AWS_KEY'),
                        'secret' => getenv('OPG_LPA_AWS_SECRET'),
                    ] : null,
                ],
                'settings' => [
                    'table_name' => getenv('OPG_LPA_CRONLOCK_DYNAMODB_TABLE') ?: 'lpa-locks-shared',
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
                    'w' => 'majority',
                    'ssl' => true,
                    'password' => getenv('OPG_LPA_API_MONGODB_PASSWORD') ?: null,
                ],

            ],

        ], // mongo


        // Used to access generated PDFs.
        'redis' => [
            'default' => [
                'host' => getenv('OPG_LPA_RESQUE_REDIS_HOST') ?: null,
                'port' => 6379,
            ],
        ], // redis

        // The queue for PDFs to be generated.
        'resque' => [

            'default' => [

                'host' => getenv('OPG_LPA_RESQUE_REDIS_HOST') ?: null,
                'port' => 6379,

            ],

        ], // resque

    ],

    'log' => [

        'path' => getenv('OPG_LPA_APPLICATION_LOG_PATH') ?: '/var/log/opg-lpa-api2/application.log',
        'sentry-uri' => getenv('OPG_LPA_SENTRY_API_URI') ?: null,

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
                    'Bucket' => getenv('OPG_LPA_PDF_CACHE_S3_BUCKET') ?: null,
                ],
                'client' => [
                    'version' => '2006-03-01',
                    'region' => 'eu-west-1',
                    'credentials' => ( getenv('OPG_LPA_AWS_KEY') && getenv('OPG_LPA_AWS_KEY') ) ? [
                        'key'    => getenv('OPG_LPA_AWS_KEY'),
                        'secret' => getenv('OPG_LPA_AWS_SECRET'),
                    ] : null,
                ],
            ], // S3

        ], // cache

    ], // pdf

);
