<?php

return array(

    'worker' => array(

        'testResponse'=>array(

            'path' => __DIR__.'/../test-data/output/',

        ),

        'redisResponse'=>array(

            'host' => getenv('OPG_LPA_PDF_CACHE_REDIS_HOST') ? getenv('OPG_LPA_PDF_CACHE_REDIS_HOST') : null,
        ),

        's3Response'=>array(
            'client' => [
                'credentials' => [
                    'key'    => getenv('OPG_LPA_PDF_CACHE_S3_KEY') ? getenv('OPG_LPA_PDF_CACHE_S3_KEY') : null,
                    'secret' => getenv('OPG_LPA_PDF_CACHE_S3_SECRET') ? getenv('OPG_LPA_PDF_CACHE_S3_SECRET') : null,
                ]
            ],
            'settings' => [
                'Bucket' => getenv('OPG_LPA_PDF_CACHE_S3_BUCKET') ? getenv('OPG_LPA_PDF_CACHE_S3_BUCKET') : null,
            ],
        ),

    ),

    'log' => [
        'path' => getenv('OPG_LPA_PDF_APPLICATION_LOG_PATH') ? getenv('OPG_LPA_PDF_APPLICATION_LOG_PATH') : null,
        'sentry-uri' => getenv('OPG_LPA_PDF_SENTRY_API_URI') ? getenv('OPG_LPA_PDF_SENTRY_API_URI') : null,
    ], // log
    
    'pdf' => [
        'encryption' => [
            // Keys MUST be a 32 character ASCII string
            'keys' => [
                'queue'     => getenv('OPG_LPA_PDF_ENCRYPTION_KEY_QUEUE') ? getenv('OPG_LPA_PDF_ENCRYPTION_KEY_QUEUE') : null,
                'document'  => getenv('OPG_LPA_PDF_ENCRYPTION_KEY_DOCUMENT') ? getenv('OPG_LPA_PDF_ENCRYPTION_KEY_DOCUMENT') : null,
            ],
            'options' => [
                'algorithm' => 'aes',
                'mode' => 'cbc',
            ],
        ],
    ], // pdf

);
