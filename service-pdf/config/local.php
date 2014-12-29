<?php

return array(

    'service' => array(

        'assets'=>array(

            'path' => __DIR__.'/../assets/',

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
