<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;

return [
    // Toggle the configuration cache. Set this to boolean false, or remove the
    // directive, to disable configuration caching. Toggling development mode
    // will also disable it by default; clear the configuration cache using
    // `composer clear-config-cache`.
    ConfigAggregator::ENABLE_CACHE => true,

    // Enable debugging; typically used to provide debugging information within templates.
    'debug' => false,

    'stack' => [
        'name' => getenv('OPG_LPA_STACK_NAME') ?: 'local',
        'environment' => getenv('OPG_LPA_STACK_ENVIRONMENT') ?: 'dev',
    ],

    'version' => [
        'tag' => getenv('OPG_DOCKER_TAG'),
    ],

    'api_base_uri' => getenv('OPG_LPA_ENDPOINTS_API') ?: null,

    // Pre-shared secret for service-to-service calls to the API.
    'service_secret' => getenv('OPG_LPA_ADMIN_SERVICE_SECRET') ?: '',

    'admin_accounts' => (
        getenv('OPG_LPA_COMMON_ADMIN_ACCOUNTS') ?
            explode(',', getenv('OPG_LPA_COMMON_ADMIN_ACCOUNTS')) : []
    ),

    'cache' => [
        'dynamodb' => [
            'client' => [
                'endpoint' => getenv('OPG_LPA_COMMON_DYNAMODB_ENDPOINT') ?: null,
                'version' => '2012-08-10',
                'region' => getenv('AWS_REGION') ?: 'eu-west-1',
            ],
            'settings' => [
                'table_name' => getenv('OPG_LPA_COMMON_ADMIN_DYNAMODB_TABLE') ?: null,
            ],
        ],
    ],

    'mezzio' => [
        // Provide templates for the error handling middleware to use when
        // generating responses.
        'error_handler' => [
            'template_404'   => 'error::404',
            'template_error' => 'error::error',
        ],
    ],
];
