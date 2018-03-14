<?php

$commit = ( is_readable('GITREF') ) ? trim(file_get_contents('GITREF')) : null;

return array(

    'version' => [
        'commit' => $commit,
        'cache' => ( !is_null($commit) ) ? abs( crc32( $commit ) ) : time(),
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

    'account-cleanup' => [
        'notification' => [
            'token' => getenv('OPG_LPA_COMMON_ACCOUNT_CLEANUP_NOTIFICATION_TOKEN') ?: null,
        ],
    ], // cleanup-cleanup

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

        'accounts' => getenv('OPG_LPA_COMMON_ADMIN_ACCOUNTS') ? explode(',',getenv('OPG_LPA_COMMON_ADMIN_ACCOUNTS')) : array(),

    ], // admin


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
                'batch_config' => [
                    // Sleep before each flush to rate limit the garbage collection.
                    'before' => function(){ sleep(1); },
                ]
            ],
        ],

        'encryption' => [
            'enabled' => true,
            // Keys must be in the format: <ident: int> => <key: 32 character ASCII string>
            'keys' => getenv('OPG_LPA_FRONT_SESSION_ENCRYPTION_KEYS') ?
                array_combine(
                    array_map( function( $v ){ return explode(':', trim($v))[0]; } , explode(',', trim(getenv('OPG_LPA_FRONT_SESSION_ENCRYPTION_KEYS')))),
                    array_map( function( $v ){ return explode(':', trim($v))[1]; } , explode(',', trim(getenv('OPG_LPA_FRONT_SESSION_ENCRYPTION_KEYS'))))
                ) : array(),
        ],

    ], // session

    'csrf' => [
        // Salt used for generating csrf tokens
        'salt' => getenv('OPG_LPA_FRONT_CSRF_SALT') ?: null,
    ],


    'api_client' => [
        'api_uri' => getenv('OPG_LPA_FRONT_ENDPOINTS_API') ?: 'https://apiv2',
        'auth_uri' => getenv('OPG_LPA_FRONT_ENDPOINTS_AUTH') ?: 'https://authv2',
    ], // api_client

    'email' => [

        'sendgrid' => [
            'user' => getenv('OPG_LPA_FRONT_EMAIL_SENDGRID_USER') ?: null,
            'key' => getenv('OPG_LPA_FRONT_EMAIL_SENDGRID_PASSWORD') ?: null,
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

        'postcodeanywhere' => [
            'key' => getenv('OPG_LPA_FRONT_POSTCODE_LICENSE_KEY') ?: null,
        ],

        'postcode_info' => [
            'uri' => getenv('OPG_LPA_FRONT_POSTCODE_INFO_URI') ?: null,
            'token' => getenv('OPG_LPA_FRONT_POSTCODE_INFO_TOKEN') ?: null,
        ],

    ], // address

    'alphagov' => [

        'pay' => [

            'key' => getenv('OPG_LPA_FRONT_GOV_PAY_KEY') ?: null,

        ],

    ],

    'worldpay' => [

        'test_mode' => ( strtolower(getenv('OPG_LPA_FRONT_WORLDPAY_TEST_MODE')) === 'true' ),
        'currency' => 'GBP',
        'cart_id' => 'LPAv2',
        'log' => false,

        'url' => getenv('OPG_LPA_FRONT_WORLDPAY_URL') ?: null,
        'merchant_code' => getenv('OPG_LPA_FRONT_WORLDPAY_MERCHANT_CODE') ?: null,
        'xml_password' => getenv('OPG_LPA_FRONT_WORLDPAY_XML_PASSWORD') ?: null,
        'administration_code' => getenv('OPG_LPA_FRONT_WORLDPAY_ADMINISTRATION_CODE') ?: null,
        'installation_id' => getenv('OPG_LPA_FRONT_WORLDPAY_INSTALLATION_ID') ?: null,
        'mac_secret' => getenv('OPG_LPA_FRONT_WORLDPAY_MAC_SECRET') ?: null,
        'api_token_secret' => getenv('OPG_LPA_FRONT_WORLDPAY_API_TOKEN_SECRET') ?: null,

    ], // worldpay

    'log' => [
        'path' => getenv('OPG_LPA_COMMON_APPLICATION_LOG_PATH') ?: '/var/log/opg-lpa-front2/application.log',
        'sentry-uri' => getenv('OPG_LPA_COMMON_SENTRY_API_URI') ?: null,
    ], // log

    'sendFeedbackEmailTo' => 'LPADigitalFeedback@PublicGuardian.gsi.gov.uk',

);
