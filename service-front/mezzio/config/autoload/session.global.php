<?php

declare(strict_types=1);

use Mezzio\Session\Ext\PhpSessionPersistence;
use Mezzio\Session\SessionPersistenceInterface;

return [
    'dependencies' => [
        'aliases' => [
            SessionPersistenceInterface::class => PhpSessionPersistence::class,
        ],
    ],
    'session' => [
        'persistence' => [
            'ext' => [
                'non_locking' => false,
            ],
        ],
    ],
];
