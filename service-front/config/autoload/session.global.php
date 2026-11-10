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
        'native_settings' => [
            // Cookie name, set per environment. Changing it signs everyone out.
            'name' => getenv('OPG_LPA_FRONT_SESSION_COOKIE_NAME') ?: 'lpa3',

            // Only send the cookie over HTTPS
            'cookie_secure' => true,

            // Prevent cookie from being accessed from JavaScript
            'cookie_httponly' => true,

            // Disable PHP session GC — Redis handles key expiry via TTL
            'gc_probability' => 0,
        ],
    ],
];
