<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'factories' => [
            \Mezzio\Flash\FlashMessageMiddleware::class => \Application\Flash\FlashMessageMiddlewareFactory::class,
        ],
    ],
];
