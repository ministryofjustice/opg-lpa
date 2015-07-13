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

        // Used to access generated PDFs.
        'redis' => [

            'default' => [

                'host' => 'redisback.local',
                'port' => 6379,

            ],

        ], // redis

        // The queue for PDFs to be generated.
        'resque' => [

            'default' => [

                'host' => 'redisback.local',
                'port' => 6379,

            ],

        ], // resque

    ],

    'pdf' => [

        'encryption' => [
            // Keys MUST be a 32 character ASCII string
            'keys' => [
                'queue' => null,      // Key for JSON pushed onto the queue
                'document' => null,   // Key for generated PDFs in the file store
            ],
            'options' => [
                'algorithm' => 'aes',
                'mode' => 'cbc',
            ],
        ],

        'cache' => [
            's3' => [

                'client' => [
                    'version' => '2006-03-01',
                    'region' => 'eu-west-1',
                ],
                'settings' => [
                    'Bucket' => 'opg-lpa-pdf-cache-dev',
                ],

            ], // S3

        ], // cache

    ], // pdf

);
