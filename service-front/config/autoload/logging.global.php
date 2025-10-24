<?php

use Monolog\Level;

return [
    'logging' => [
        'serviceName' => 'opg-lpa/front',
        'minLevel' => Level::fromName("DEBUG"),
    ]
];
