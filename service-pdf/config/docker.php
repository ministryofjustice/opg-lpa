<?php

return call_user_func(function(){

    $config = array(

        'worker' => array(

            's3Response'=>array(
                'client' => [
                    'credentials' => [
                        'key'    => 'OPG_LPA_PDF_CACHE_S3_KEY',
                        'secret' => 'OPG_LPA_PDF_CACHE_S3_SECRET',
                    ]
                ],
                'settings' => [
                    'Bucket' => 'OPG_LPA_PDF_CACHE_S3_BUCKET',
                ],
            ),

        ),

        'log' => [
            'path' => 'OPG_LPA_PDF_APPLICATION_LOG_PATH',
            'sentry-uri' => 'OPG_LPA_PDF_SENTRY_API_URI',
        ], // log

        'pdf' => [
            'encryption' => [
                // Keys MUST be a 32 character ASCII string
                'keys' => [
                    'queue'     => 'OPG_LPA_PDF_ENCRYPTION_KEY_QUEUE',
                    'document'  => 'OPG_LPA_PDF_ENCRYPTION_KEY_DOCUMENT',
                ],
            ],
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

