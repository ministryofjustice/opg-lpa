<?php

return [

    'service_manager' => [
        'abstract_factories' => [
            'Auth\Model\Service\ServiceAbstractFactory',
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ],
    ],

];
