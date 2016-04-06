<?php

return call_user_func(function(){

    $config = array(

        'stack' => [
            'name' => 'OPG_LPA_STACK_NAME',
            'environment' => 'OPG_LPA_STACK_ENVIRONMENT',
        ],


        'account-cleanup' => [
            'notification' => [
                'token' => 'OPG_LPA_FRONT_ACCOUNT_CLEANUP_NOTIFICATION_TOKEN',
            ],
        ], // cleanup-cleanup

        'session' => [

            'encryption' => [
                // Key MUST be a 32 character string
                'key' => 'OPG_LPA_FRONT_SESSION_ENCRYPTION_KEY',
            ],

            'redis' => [
                'server' => [
                    'host' => 'OPG_LPA_FRONT_SESSION_REDIS_HOST',
                ],
            ],

            'dynamodb' => [
                'settings' => [
                    'table_name' => 'OPG_LPA_FRONT_SESSION_DYNAMODB_TABLE',
                ],
                'client' => [
                    'credentials' => [
                        'key'    => 'OPG_LPA_FRONT_SESSION_DYNAMODB_KEY',
                        'secret' => 'OPG_LPA_FRONT_SESSION_DYNAMODB_SECRET',
                    ]
                ],
            ],



        ], // session

        'cron' => [

            'lock' => [

                'dynamodb' => [
                    'settings' => [
                        'table_name' => 'OPG_LPA_FRONT_CRONLOCK_DYNAMODB_TABLE',
                    ],
                    'client' => [
                        'credentials' => [
                            'key'    => 'OPG_LPA_FRONT_CRONLOCK_DYNAMODB_KEY',
                            'secret' => 'OPG_LPA_FRONT_CRONLOCK_DYNAMODB_SECRET',
                        ]
                    ],
                ],

            ], // lock

        ], // cron

        'csrf' => [
            // Salt used for generating csrf tokens
            'salt' => 'OPG_LPA_FRONT_CSRF_SALT',
        ],

        'admin' => [

            'dynamodb' => [
                'settings' => [
                    'table_name' => 'OPG_LPA_FRONT_ADMIN_DYNAMODB_TABLE',
                ],
                'client' => [
                    'credentials' => [
                        'key'    => 'OPG_LPA_FRONT_ADMIN_DYNAMODB_KEY',
                        'secret' => 'OPG_LPA_FRONT_ADMIN_DYNAMODB_SECRET',
                    ]
                ],
            ],

            'accounts' => [
                //'OPG_LPA_FRONT_ADMIN_ACCOUNTS') ? explode(',','OPG_LPA_FRONT_ADMIN_ACCOUNTS')) : null,
            ],
        ],

        'email' => [

            'sendgrid' => [
                'user' => 'OPG_LPA_FRONT_EMAIL_SENDGRID_USER',
                'key' => 'OPG_LPA_FRONT_EMAIL_SENDGRID_PASSWORD',
            ], //sendgrid

        ], // email

        'api_client' => [
            'api_uri' => 'OPG_LPA_FRONT_ENDPOINTS_API',
            'auth_uri' => 'OPG_LPA_FRONT_ENDPOINTS_AUTH',
        ], // api_client

        'address' => [

            'postcodeanywhere' => [
                'key' => 'OPG_LPA_FRONT_POSTCODE_LICENSE_KEY',
            ],

            'postcode_info' => [
                'uri' => 'OPG_LPA_FRONT_POSTCODE_INFO_URI',
                'token' => 'OPG_LPA_FRONT_POSTCODE_INFO_TOKEN',
            ],

        ], // address

        'log' => [
            'path' => 'OPG_LPA_FRONT_LOG_PATH',
            'sentry-uri' => 'OPG_LPA_FRONT_SENTRY_API_URI',
        ], // log

        'worldpay' => [
            'url' => 'OPG_LPA_FRONT_WORLDPAY_URL',
            'merchant_code' => 'OPG_LPA_FRONT_WORLDPAY_MERCHANT_CODE',
            'xml_password' => 'OPG_LPA_FRONT_WORLDPAY_XML_PASSWORD',
            'administration_code' => 'OPG_LPA_FRONT_WORLDPAY_ADMINISTRATION_CODE',
            'installation_id' => 'OPG_LPA_FRONT_WORLDPAY_INSTALLATION_ID',
            'mac_secret' => 'OPG_LPA_FRONT_WORLDPAY_MAC_SECRET',
            'api_token_secret' => 'OPG_LPA_FRONT_WORLDPAY_API_TOKEN_SECRET',
        ], // worldpay

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


