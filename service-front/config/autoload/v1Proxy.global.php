<?php

return array(

    'v1proxy' => [

        'cache-no-laps' => false,

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
