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

            'host' => 'redisback.local',
            'port' => 6379,

            // The number of files that can be stored in Redis.
            'size' => 30,

        ),

    ),
);
