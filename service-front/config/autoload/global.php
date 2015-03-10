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

        'encryption' => [
            'enabled' => true,
            // Key MUST be a 32 character ASCII string
            'key' => 'insecure-encryption-session-key!'
        ],

    ], // session

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

        'test_mode' => true,
        'url' => 'https://secure-test.worldpay.com/wcc/purchase',
        'currency' => 'GBP',
        'cart_id' => 'LPAv2',
        'log' => false,

    ], // worldpay
    
    'sendFeedbackEmailTo' => 'LPADigitalFeedback@PublicGuardian.gsi.gov.uk',

);
