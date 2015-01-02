<?php

/**
 * Include the insecure vagrant details by default.
 * These should be overridden in local.php
 */
return array(

    'db' => [

        'mongo' => [

            'default' => [

                'hosts' => [ 'mongodb-04.local:27017', 'mongodb-05.local:27017', 'mongodb-06.local:27017' ],
                'options' => [
                    'db' => 'opglpa-api',
                    'username' => 'opglpa-api',
                    'password' => 'insecure_vagrant_password2',
                    'replicaSet' => 'rs0',
                    'connect' => false,
                    'connectTimeoutMS' => 1000,
                    'w' => 'majority'
                ],

            ],

        ], // mongo

        'redis' => [

            'default' => [

                'host' => 'redisback.local',
                'port' => 6379,

            ],

        ], // redis

        'resque' => [

            'default' => [

                'host' => 'redisback.local',
                'port' => 6379,

            ],

        ], // resque

    ],

);
