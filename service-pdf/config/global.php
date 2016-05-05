<?php

return array(

    'service' => array(

        'assets'=>array(
            'source_template_path' => __DIR__.'/../assets/v2',
            'template_path_on_ram_disk' => '/tmp/pdf_ramdisk/assets/v2',
            'intermediate_file_path' => '/tmp/pdf_ramdisk'
        ),

    ),

    'worker' => array(

        'testResponse'=>array(

            'path' => __DIR__.'/../test-data/output/',

        ),

        's3Response'=>array(
            'client' => [
                'version' => '2006-03-01',
                'region' => 'eu-west-1',
                'credentials' => ( getenv('AWS_ACCESS_KEY_ID') && getenv('AWS_SECRET_ACCESS_KEY') ) ? [
                    'key'    => getenv('AWS_ACCESS_KEY_ID'),
                    'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
                ] : null,
            ],
            'settings' => [
                'ACL' => 'private',
                'Bucket' => getenv('OPG_LPA_COMMON_PDF_CACHE_S3_BUCKET') ?: null,
            ],
        ),

    ), // worker

    'log' => [
        'path' => getenv('OPG_LPA_COMMON_APPLICATION_LOG_PATH') ?: '/var/log/opg-lpa-pdf2/application.log',
        'sentry-uri' => getenv('OPG_LPA_COMMON_SENTRY_API_URI') ?: null,
    ], // log


    'pdf' => [
        'encryption' => [
            // Keys MUST be a 32 character ASCII string
            'keys' => [
                'queue'     => getenv('OPG_LPA_PDF_ENCRYPTION_KEY_QUEUE') ?: null,
                'document'  => getenv('OPG_LPA_PDF_ENCRYPTION_KEY_DOCUMENT') ?: null,
            ],
            'options' => [
                'algorithm' => 'aes',
                'mode' => 'cbc',
            ],
        ],
    ], // pdf

    'footer' => [
        'lp1f' => [
            'instrument'   => 'LP1F Property and financial affairs (07.15)',
            'registration' => 'LP1F Register your LPA (07.15)'
            ],
        'lp1h' => [
            'instrument'   => 'LP1H Health and welfare (07.15)',
            'registration' => 'LP1H Register your LPA (07.15)'
            ],
        'cs1' => 'LPC Continuation sheet 1 (07.15)',
        'cs2' => 'LPC Continuation sheet 2 (07.15)',
        'cs3' => 'LPC Continuation sheet 3 (07.15)',
        'cs4' => 'LPC Continuation sheet 4 (07.15)',
        'lp3' => 'LP3 People to notify (07.15)',
    ], // footer
);
