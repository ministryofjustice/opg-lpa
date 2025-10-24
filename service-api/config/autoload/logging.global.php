<?php

use Monolog\Level;

return [
    'logging' => [
        'serviceName' => 'opg-lpa/api',
        'minLevel' => Level::fromName("DEBUG"),
    ]
];
