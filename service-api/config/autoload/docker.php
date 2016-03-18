<?php

return call_user_func(function(){

    $config = array(
        'stack' => [
            'name' => 'OPG_LPA_STACK_NAME',
            'environment' => 'OPG_LPA_STACK_ENVIRONMENT',
        ],


        'authentication' => [
            'endpoint' => 'OPG_LPA_API_ENDPOINTS_AUTH',
        ],

        'cron' => [

            'lock' => [

                'dynamodb' => [
                    'settings' => [
                        'table_name' => 'OPG_LPA_API_CRONLOCK_DYNAMODB_TABLE',
                    ],
                    'client' => [
                        'credentials' => [
                            'key'    => 'OPG_LPA_API_CRONLOCK_DYNAMODB_KEY',
                            'secret' => 'OPG_LPA_API_CRONLOCK_DYNAMODB_SECRET',
                        ]
                    ],
                ],

            ], // lock

        ], // cron


        'db' => [

            'mongo' => [

                'default' => [

                    'options' => [
                        'password' => 'OPG_LPA_API_MONGODB_PASSWORD',
                    ],

                ],

            ], // mongo

            // Used to access generated PDFs.
            'redis' => [

                'default' => [

                    'host' => 'OPG_LPA_API_PDF_CACHE_REDIS_HOST',

                ],

            ], // redis

            // The queue for PDFs to be generated.
            'resque' => [

                'default' => [

                    'host' => 'OPG_LPA_API_RESQUE_REDIS_HOST',

                ],

            ], // resque

        ], // db

        'log' => [
            'path' => 'OPG_LPA_API_LOG_PATH',
            'sentry-uri' => 'OPG_LPA_API_SENTRY_API_URI',
        ], // log

        'pdf' => [

            'encryption' => [
                // Keys MUST be a 32 character ASCII string
                'keys' => [
                    'queue'     => 'OPG_LPA_API_PDF_ENCRYPTION_KEY_QUEUE',
                    'document'  => 'OPG_LPA_API_PDF_ENCRYPTION_KEY_DOCUMENT',
                ],
            ],


            'cache' => [
                's3' => [
                    'settings' => [
                        'Bucket' => 'OPG_LPA_API_PDF_CACHE_S3_BUCKET',
                    ],
                    'client' => [
                        'credentials' => [
                            'key'    => 'OPG_LPA_API_PDF_CACHE_S3_KEY',
                            'secret' => 'OPG_LPA_API_PDF_CACHE_S3_SECRET',
                        ]
                    ],
                ], // S3
            ], // cache

        ], // pdf
        
    ); // config array

    $filter = function(array &$array) use(&$filter) {

        foreach ($array as $key => &$value) {

            // If the value is an array...
            if (is_array($value)) {

                // Apply this function over that array...
                $value = $filter($value);

                // If an empty array way returned, we can remove it...
                if (is_array($value) && count($value) == 0) {
                    unset($array[$key]);
                }

            } else {

                // Else it's a variable.

                // See if it's in the environment...
                if (getenv($value)) {

                    // If so, replace the ENV name with the value.
                    $array[$key] = getenv($value);

                } else {

                    // Else drop the key
                    unset($array[$key]);

                } // if

            } // if

        } // foreach

        return $array;

    };

    return $filter($config);

});

