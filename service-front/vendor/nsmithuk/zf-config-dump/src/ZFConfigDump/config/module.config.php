<?php

return [

    'controllers' => [
        'invokables' => [
            'DumpConfigController' => 'ZFConfigDump\Controller\DumpConfigController',
        ],
    ],

    'console' => [
        'router' => [
            'routes' => [
                'zf-config-dump' => [
                    'type'    => 'simple',
                    'options' => [
                        'route'    => 'dump-config [<filter>]',
                        'defaults' => [
                            'controller' => 'DumpConfigController',
                            'action'     => 'dump'
                        ],
                    ],
                ],
            ],
        ],
    ],

];