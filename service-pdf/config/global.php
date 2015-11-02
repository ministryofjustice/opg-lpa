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

        'redisResponse'=>array(

            'host' => 'redisback',
            'port' => 6379,

            // The number of files that can be stored in Redis.
            'size' => 30,

        ),

    ),

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
