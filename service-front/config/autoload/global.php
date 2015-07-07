<?php

$commit = ( is_readable('GITREF') ) ? trim(file_get_contents('GITREF')) : null;

return array(

    'version' => [
        'commit' => $commit,
        'cache' => ( !is_null($commit) ) ? abs( crc32( $commit ) ) : time(),
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
        'redis' => [
            // Set a default (longish) Redis TTL to protect against long term stale data.
            'ttl' => (60 * 60 * 24 * 28), // 28 days
            'namespace' => 'session',
            'server' => [
                'host' => 'redisfront.local',
                'port' => 6379
            ],
            'database' => 1, // WARNING: this has to be defined last otherwise Zend\Cache has a hissy fit.
        ],
    ],
    
    'session' => [

        // ini session.* settings...
        'native_settings' => [

            // The cookie name used in the session
            'name' => 'seshy',

            // Hash settings
            'hash_function' => 'sha512',
            'hash_bits_per_character' => 5,

            // Only allow the cookie to be sent over https
            'cookie_secure' => false, # TODO - change to true once we have SSL on dev.

            // Prevent cookie from being accessed from JavaScript
            'cookie_httponly' => true,

            // Don't accept uninitialized session IDs
            'use_strict_mode' => true,
        ],

        'redis' => [
            // Set a default (longish) Redis TTL to protect against long term stale data.
            'ttl' => (60 * 60 * 24 * 7), // 7 days
            'namespace' => 'session',
            'server' => [
                'host' => 'redisfront.local',
                'port' => 6379
            ],
            'database' => 0, // WARNING: this has to be defined last otherwise Zend\Cache has a hissy fit.
        ],

        'dynamodb' => [
            'client' => [
                'version' => '2012-08-10',
                'region' => 'eu-west-1',
                'credentials' => [
                    'key'    => null,
                    'secret' => null
                ]
            ],
            'settings' => [
                'table_name' => 'lpa-sessions',
            ],
        ],

        'encryption' => [
            'enabled' => true,
            // Key MUST be a 32 character ASCII string
            'key' => null
        ],

    ], // session

    'csrf' => [
        // Salt used for generating csrf tokens
        'salt' => 'csrf-secret',
    ],

    'email' => [

        'sendgrid' => [
            'user' => null,
            'key' => null,
        ], //sendgrid

    ], // email

    'address' => [

        'postcodeanywhere' => [
            'key' => null,
        ],

    ], // postcode

    'worldpay' => [

        'test_mode' => false,
        'currency' => 'GBP',
        'cart_id' => 'LPAv2',
        'log' => false,

    ], // worldpay
    
    'sendFeedbackEmailTo' => 'LPADigitalFeedback@PublicGuardian.gsi.gov.uk',

    #v1Code
    'v1proxy' => [

        // Should we cache the fact there are no v1 LPAs in a user's account.
        // Should be TRUE in production.
        'cache-no-lpas' => true,

        // Should we allow new v1 LPAs to be created.
        // Should be FALSE in production.
        'allow-v1-laps-to-be-created' => false,

        'redis' => [
            // This data should persist for the length v1 is alive (6 months predicted).
            'ttl' => (86400 * 365), // 365 days; leave room for delays.
            'namespace' => 'v1proxy',
            'server' => [
                'host' => 'redisfront.local',
                'port' => 6379
            ],
            'database' => 2, // WARNING: this has to be defined last otherwise Zend\Cache has a hissy fit.
        ],

    ],

);
