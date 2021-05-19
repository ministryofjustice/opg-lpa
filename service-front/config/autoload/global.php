<?php

return [

    'version' => [
        'cache' => ( getenv('OPG_DOCKER_TAG') !== false ) ? abs( crc32( getenv('OPG_DOCKER_TAG') ) ) : time(),
        'tag' => getenv('OPG_DOCKER_TAG'),
    ],

    'stack' => [
        'name' => getenv('OPG_LPA_STACK_NAME') ?: 'local',
        'environment' => getenv('OPG_LPA_STACK_ENVIRONMENT') ?: 'dev',
    ],

    'terms' => [
        // The date and time the terms were last updated.
        // Users who have not logged in since this date will see the 'T&Cs updated' page.
        'lastUpdated' => '2015-02-17 14:00 UTC',
    ],

    'redirects' => [
        'index' => 'https://www.gov.uk/power-of-attorney/make-lasting-power',
        'logout' => 'https://www.gov.uk/done/lasting-power-of-attorney',
    ],

    'admin' => [

        'dynamodb' => [
            'client' => getDynamoClientConfig(),
            'settings' => [
                'table_name' => getenv('OPG_LPA_COMMON_ADMIN_DYNAMODB_TABLE') ?: 'lpa-properties-shared',
            ],
            'auto_create' => getenv('OPG_LPA_COMMON_DYNAMODB_AUTO_CREATE') ?: false,
        ],

    ], // admin

    'cron' => [

        'lock' => [

            'dynamodb' => [
                'client' => getDynamoClientConfig(),
                'settings' => [
                    'table_name' => getenv('OPG_LPA_COMMON_CRONLOCK_DYNAMODB_TABLE') ?: 'lpa-locks-shared',
                ],
                'auto_create' => getenv('OPG_LPA_COMMON_DYNAMODB_AUTO_CREATE') ?: false,
            ],

        ], // lock

    ], // cron


    'session' => [

        // ini session.* settings...
        'native_settings' => [

            // The cookie name used in the session
            'name' => 'lpa',

            // Hash settings
            'hash_function' => 'sha512',
            'hash_bits_per_character' => 5,

            // Only allow the cookie to be sent over https, if we're using HTTPS.
            'cookie_secure' => true,

            // Prevent cookie from being accessed from JavaScript
            'cookie_httponly' => true,

            // Don't accept uninitialized session IDs
            'use_strict_mode' => true,

            // Time before a session can be garbage collected.
            // (time since the session was last accessed)
            'gc_maxlifetime' => (60 * 60 * 3), // 3 hours

            // The probability of GC running is gc_probability/gc_divisor
            'gc_probability' => 0,
            'gc_divisor' => 20,
        ],

        'dynamodb' => [
            'client' => getDynamoClientConfig(),
            'settings' => [
                'table_name' => getenv('OPG_LPA_COMMON_SESSION_DYNAMODB_TABLE') ?: 'lpa-sessions-shared',
                // Whether Time To Live is enabled on the sesson table
                'ttl_enabled' => getenv('OPG_LPA_COMMON_SESSION_DYNAMODB_TTL_ENABLED') ?: true,
                // The DB field to use for the Time To Live expiry time
                'ttl_attribute' => getenv('OPG_LPA_COMMON_SESSION_DYNAMODB_TTL_ATTRIBUTE') ?: 'expires',
                'batch_config' => [
                    // Sleep before each flush to rate limit the garbage collection.
                    'before' => function(){ },
                ]
            ],
            'auto_create' => getenv('OPG_LPA_COMMON_DYNAMODB_AUTO_CREATE') ?: false,
        ],

    ], // session

    'csrf' => [
        // Salt used for generating csrf tokens
        'salt' => getenv('OPG_LPA_FRONT_CSRF_SALT') ?: null,
    ],


    'api_client' => [
        'api_uri' => getenv('OPG_LPA_ENDPOINTS_API') ?: null,
    ],

    'email' => [

        'sendgrid' => [
            'key'     => getenv('OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY') ?: null,
            'webhook' => [
                'token' => getenv('OPG_LPA_FRONT_EMAIL_SENDGRID_WEBHOOK_TOKEN') ?: null,
            ],
        ], //sendgrid

        'sender' => [
                'default' => [
                        'name' => 'Office of the Public Guardian',
                        'address' => 'opg@lastingpowerofattorney.service.gov.uk',
                ],

                'feedback' => [
                        'name' => 'User Feedback',
                        'address' => 'opg@lastingpowerofattorney.service.gov.uk',
                ],
        ], // opg email sender
    ], // email


    'address' => [

        'ordnancesurvey' => [
            'key' => getenv('OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY') ?: null,
            'endpoint' => getenv('OPG_LPA_OS_PLACES_HUB_ENDPOINT') ?: null,
        ],

    ], // address

    'alphagov' => [

        'pay' => [

            'key' => getenv('OPG_LPA_FRONT_GOV_PAY_KEY') ?: null,

        ],

    ],

    'log' => [
        'path' => getenv('OPG_LPA_COMMON_APPLICATION_LOG_PATH') ?: '/var/log/opg-lpa-front2/application.log',
        'sentry-uri' => getenv('OPG_LPA_COMMON_SENTRY_API_URI') ?: null,
    ], // log

    'sendFeedbackEmailTo' => 'LPADigitalFeedback@PublicGuardian.gov.uk',
    'processing-status' => [
        'track-from-date' => getenv('OPG_LPA_FRONT_TRACK_FROM_DATE') ?: '2019-04-01',

        // Number of working days after an LPA is processed before we expect it
        // to be received by the user
        'expected-working-days-before-receipt' => 15,
    ],
];

function getDynamoClientConfig()
{
    return [
        'endpoint' => getenv('OPG_LPA_COMMON_DYNAMODB_ENDPOINT') ?: null,
        'version' => '2012-08-10',
        'region' => 'eu-west-1',
    ];
}
