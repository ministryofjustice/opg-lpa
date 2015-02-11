<?php

return array(

    'v1proxy' => [

        // Should we cache the fact there are no v1 LPAs in a user's account.
        // Should be TRUE in production.
        'cache-no-laps' => false,

        // Should we allow new v1 LPAs to be created.
        // Should be FALSE in production.
        'allow-v1-laps-to-be-created' => true,

        'redis' => [
            // This data should persist for the length v1 is alive (6 months predicted).
            'ttl' => (86400 * 365), // 365 days; leave room for delays.
            'namespace' => 'v1proxy',
            'server' => [
                'host' => 'redisfront.local',
                'port' => 6379
            ],
            'database' => 1, // WARNING: this has to be defined last otherwise Zend\Cache has a hissy fit.
        ],

    ],

);
