<?php

declare(strict_types=1);

use Zend\ConfigAggregator\ConfigAggregator;

return [
    // Toggle the configuration cache. Set this to boolean false, or remove the
    // directive, to disable configuration caching. Toggling development mode
    // will also disable it by default; clear the configuration cache using
    // `composer clear-config-cache`.
    ConfigAggregator::ENABLE_CACHE => true,

    // Enable debugging; typically used to provide debugging information within templates.
    'debug' => false,

    'api_base_uri' => getenv('OPG_LPA_ENDPOINTS_API') ?: 'https://apiv2',

    'admin_accounts' => (getenv('OPG_LPA_COMMON_ADMIN_ACCOUNTS') ? explode(',', getenv('OPG_LPA_COMMON_ADMIN_ACCOUNTS')) : []),

    'jwt' => [
        'secret'    => getenv('OPG_LPA_ADMIN_JWT_SECRET') ?: null,
        'path'      => '/',
        'header'    => 'lpa-admin',
        'cookie'    => 'lpa-admin',
        'ttl'       => 60 * 60,
        'algo'      => 'HS256',
    ],

    'zend-expressive' => [
        // Provide templates for the error handling middleware to use when
        // generating responses.
        'error_handler' => [
            'template_404'   => 'error::404',
            'template_error' => 'error::error',
        ],
    ],
];
