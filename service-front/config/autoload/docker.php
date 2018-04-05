<?php

return array(

    'stack' => [
        'name' => getenv('OPG_LPA_STACK_NAME') ? getenv('OPG_LPA_STACK_NAME') : null,
        'environment' => getenv('OPG_LPA_STACK_ENVIRONMENT') ? getenv('OPG_LPA_STACK_ENVIRONMENT') : null,
    ],


    'account-cleanup' => [
        'notification' => [
            'token' => getenv('OPG_LPA_FRONT_ACCOUNT_CLEANUP_NOTIFICATION_TOKEN') ? getenv('OPG_LPA_FRONT_ACCOUNT_CLEANUP_NOTIFICATION_TOKEN') : null,
        ],
    ], // cleanup-cleanup

    'session' => [

        'encryption' => [
            // Key MUST be a 32 character string
            'key' => getenv('OPG_LPA_FRONT_SESSION_ENCRYPTION_KEY') ? getenv('OPG_LPA_FRONT_SESSION_ENCRYPTION_KEY') : null,
        ],

        'redis' => [
            'server' => [
                'host' => getenv('OPG_LPA_FRONT_SESSION_REDIS_HOST') ? getenv('OPG_LPA_FRONT_SESSION_REDIS_HOST') : null,
            ],
        ],

        'dynamodb' => [
            'settings' => [
                'table_name' => getenv('OPG_LPA_FRONT_SESSION_DYNAMODB_TABLE') ? getenv('OPG_LPA_FRONT_SESSION_DYNAMODB_TABLE') : null,
            ],
            'client' => [
                'credentials' => [
                    'key'    => getenv('OPG_LPA_FRONT_SESSION_DYNAMODB_KEY') ? getenv('OPG_LPA_FRONT_SESSION_DYNAMODB_KEY') : null,
                    'secret' => getenv('OPG_LPA_FRONT_SESSION_DYNAMODB_SECRET') ? getenv('OPG_LPA_FRONT_SESSION_DYNAMODB_SECRET') : null,
                ]
            ],
        ],



    ], // session

    'cron' => [

        'lock' => [

            'dynamodb' => [
                'settings' => [
                    'table_name' => getenv('OPG_LPA_FRONT_CRONLOCK_DYNAMODB_TABLE') ? getenv('OPG_LPA_FRONT_CRONLOCK_DYNAMODB_TABLE') : null,
                ],
                'client' => [
                    'credentials' => [
                        'key'    => getenv('OPG_LPA_FRONT_CRONLOCK_DYNAMODB_KEY') ? getenv('OPG_LPA_FRONT_CRONLOCK_DYNAMODB_KEY') : null,
                        'secret' => getenv('OPG_LPA_FRONT_CRONLOCK_DYNAMODB_SECRET') ? getenv('OPG_LPA_FRONT_CRONLOCK_DYNAMODB_SECRET') : null,
                    ]
                ],
            ],

        ], // lock

    ], // cron

    'csrf' => [
        // Salt used for generating csrf tokens
        'salt' => getenv('OPG_LPA_FRONT_CSRF_SALT') ? getenv('OPG_LPA_FRONT_CSRF_SALT') : null,
    ],

    'admin' => [

        'dynamodb' => [
            'settings' => [
                'table_name' => getenv('OPG_LPA_FRONT_ADMIN_DYNAMODB_TABLE') ? getenv('OPG_LPA_FRONT_ADMIN_DYNAMODB_TABLE') : null,
            ],
            'client' => [
                'credentials' => [
                    'key'    => getenv('OPG_LPA_FRONT_ADMIN_DYNAMODB_KEY') ? getenv('OPG_LPA_FRONT_ADMIN_DYNAMODB_KEY') : null,
                    'secret' => getenv('OPG_LPA_FRONT_ADMIN_DYNAMODB_SECRET') ? getenv('OPG_LPA_FRONT_ADMIN_DYNAMODB_SECRET') : null,
                ]
            ],
        ],

        'accounts' => [
            getenv('OPG_LPA_FRONT_ADMIN_ACCOUNTS') ? explode(',',getenv('OPG_LPA_FRONT_ADMIN_ACCOUNTS')) : null,
        ],
    ],

    'email' => [

        'sendgrid' => [
            'user' => getenv('OPG_LPA_FRONT_EMAIL_SENDGRID_USER') ? getenv('OPG_LPA_FRONT_EMAIL_SENDGRID_USER') : null,
            'key' => getenv('OPG_LPA_FRONT_EMAIL_SENDGRID_PASSWORD') ? getenv('OPG_LPA_FRONT_EMAIL_SENDGRID_PASSWORD') : null,
        ], //sendgrid

    ], // email

    'api_client' => [
        'api_uri' => getenv('OPG_LPA_FRONT_ENDPOINTS_API') ? getenv('OPG_LPA_FRONT_ENDPOINTS_API') : null,
        'auth_uri' => getenv('OPG_LPA_FRONT_ENDPOINTS_AUTH') ? getenv('OPG_LPA_FRONT_ENDPOINTS_AUTH') : null,
    ], // api_client

    'address' => [

        'postcodeanywhere' => [
            'key' => getenv('OPG_LPA_FRONT_POSTCODE_LICENSE_KEY') ? getenv('OPG_LPA_FRONT_POSTCODE_LICENSE_KEY') : null,
        ],

        'postcode_info' => [
            'uri' => getenv('OPG_LPA_FRONT_POSTCODE_INFO_URI') ? getenv('OPG_LPA_FRONT_POSTCODE_INFO_URI') : null,
            'token' => getenv('OPG_LPA_FRONT_POSTCODE_INFO_TOKEN') ? getenv('OPG_LPA_FRONT_POSTCODE_INFO_TOKEN') : null,
        ],

    ], // address

    'log' => [
        'path' => getenv('OPG_LPA_FRONT_LOG_PATH') ? getenv('OPG_LPA_FRONT_LOG_PATH') : null,
        'sentry-uri' => getenv('OPG_LPA_FRONT_SENTRY_API_URI') ? getenv('OPG_LPA_FRONT_SENTRY_API_URI') : null,
    ], // log

);