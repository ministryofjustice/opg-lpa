<?php

/**
 * Application-level configuration for the Mezzio app.
 *
 * Migrated from module/Application/config/module.config.php.
 * MVC-specific keys (view_manager, view_helpers) are intentionally omitted —
 * Twig handles rendering in this app and those keys have no effect in Mezzio.
 */

declare(strict_types=1);

return [
    // Email template paths used by the mail transport service.
    // Migrated from module.config.php email_view_manager.
    'email_view_manager' => [
        'template_path_stack' => [
            'emails' => __DIR__ . '/../../../../module/Application/view/email',
        ],
    ],
];
