<?php

use Monolog\Level;

return [
    'logging' => [
        'serviceName' => 'opg-lpa/admin',
        'minLevel' => Level::Info,
        'metricNamespace' => getenv('OPG_LPA_STACK_NAME') ?: 'mlpa',
    ]
];
