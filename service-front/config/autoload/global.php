<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return array(

    'session' => [

        'redis' => [
            // Set a default (longish) Redis TTL to protect against long term stale data.
            'ttl' => (60 * 60 * 24 * 28), // 28 days
            'namespace' => 'session',
            'server' => [
                'host' => 'redisfront.local',
                'port' => 6379
            ],
            'database' => 0, // WARNING: this has to be defined last otherwise Zend\Cache has a hissy fit.
        ],

        'encryption' => [
            // key has to be a 32 character string
            'key' => 'insecure-encryption-session-key!'
        ]

    ],

);

