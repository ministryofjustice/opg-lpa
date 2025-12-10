<?php

use Monolog\Level;

return [
    'logging' => [
        'serviceName' => 'opg-lpa/admin',
        'minLevel' => Level::fromName("DEBUG"),
    ]
];
