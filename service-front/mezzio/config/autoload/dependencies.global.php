<?php

declare(strict_types=1);

use App\Handler;
use App\View;

return [
    'dependencies' => [
        'aliases' => [],
        'invokables' => [],
        'factories' => [
            // Handlers
            Handler\HomeHandler::class  => Handler\HomeHandlerFactory::class,
            Handler\LoginHandler::class => Handler\Factory\LoginHandlerFactory::class,

            // View extensions
            View\Twig\LegacyCompatExtension::class => View\Twig\LegacyCompatExtensionFactory::class,
        ],
    ],
];
