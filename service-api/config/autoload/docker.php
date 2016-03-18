<?php

return array(

    'stack' => [
        'name' => getenv('OPG_LPA_STACK_NAME') ? getenv('OPG_LPA_STACK_NAME') : null,
        'environment' => getenv('OPG_LPA_STACK_ENVIRONMENT') ? getenv('OPG_LPA_STACK_ENVIRONMENT') : null,
    ],


    'authentication' => [
        'endpoint' => getenv('OPG_LPA_API_ENDPOINTS_AUTH') ? getenv('OPG_LPA_API_ENDPOINTS_AUTH') : null,
    ],

    'cron' => [

        'lock' => [

            'dynamodb' => [
                'settings' => [
                    'table_name' => getenv('OPG_LPA_API_CRONLOCK_DYNAMODB_TABLE') ? getenv('OPG_LPA_API_CRONLOCK_DYNAMODB_TABLE') : null,
                ],
                'client' => [
                    'credentials' => [
                        'key'    => getenv('OPG_LPA_API_CRONLOCK_DYNAMODB_KEY') ? getenv('OPG_LPA_API_CRONLOCK_DYNAMODB_KEY') : null,
                        'secret' => getenv('OPG_LPA_API_CRONLOCK_DYNAMODB_SECRET') ? getenv('OPG_LPA_API_CRONLOCK_DYNAMODB_SECRET') : null,
                    ]
                ],
            ],

        ], // lock

    ], // cron


    'db' => [

        'mongo' => [

            'default' => [

                'options' => [
                    'password' => getenv('OPG_LPA_API_MONGODB_PASSWORD') ? getenv('OPG_LPA_API_MONGODB_PASSWORD') : null,
                ],

            ],

        ], // mongo

        // Used to access generated PDFs.
        'redis' => [

            'default' => [

                'host' => getenv('OPG_LPA_API_PDF_CACHE_REDIS_HOST') ? getenv('OPG_LPA_API_PDF_CACHE_REDIS_HOST') : null,

            ],

        ], // redis

        // The queue for PDFs to be generated.
        'resque' => [

            'default' => [

                'host' => getenv('OPG_LPA_API_RESQUE_REDIS_HOST') ? getenv('OPG_LPA_API_RESQUE_REDIS_HOST') : null,

            ],

        ], // resque

    ], // db
    
    'log' => [
        'path' => getenv('OPG_LPA_API_LOG_PATH') ? getenv('OPG_LPA_API_LOG_PATH') : null,
        'sentry-uri' => getenv('OPG_LPA_API_SENTRY_API_URI') ? getenv('OPG_LPA_API_SENTRY_API_URI') : null,
    ], // log

    'pdf' => [

        'encryption' => [
            // Keys MUST be a 32 character ASCII string
            'keys' => [
                'queue'     => getenv('OPG_LPA_API_PDF_ENCRYPTION_KEY_QUEUE') ? getenv('OPG_LPA_API_PDF_ENCRYPTION_KEY_QUEUE') : null,
                'document'  => getenv('OPG_LPA_API_PDF_ENCRYPTION_KEY_DOCUMENT') ? getenv('OPG_LPA_API_PDF_ENCRYPTION_KEY_DOCUMENT') : null,
            ],
        ],


        'cache' => [
            's3' => [
                'settings' => [
                    'Bucket' => getenv('OPG_LPA_API_PDF_CACHE_S3_BUCKET') ? getenv('OPG_LPA_API_PDF_CACHE_S3_BUCKET') : null,
                ],
                'client' => [
                    'credentials' => [
                        'key'    => getenv('OPG_LPA_API_PDF_CACHE_S3_KEY') ? getenv('OPG_LPA_API_PDF_CACHE_S3_KEY') : null,
                        'secret' => getenv('OPG_LPA_API_PDF_CACHE_S3_SECRET') ? getenv('OPG_LPA_API_PDF_CACHE_S3_SECRET') : null,
                    ]
                ],
            ], // S3
        ], // cache

    ], // pdf

);
