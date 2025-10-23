<?php

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'default',
        'default' => [
            'adapter' => 'pgsql',
            'host' => getenv('OPG_LPA_POSTGRES_HOSTNAME'),
            'name' => getenv('OPG_LPA_POSTGRES_NAME'),
            'user' => getenv('OPG_LPA_POSTGRES_USERNAME'),
            'pass' => getenv('OPG_LPA_POSTGRES_PASSWORD'),
            'port' => getenv('OPG_LPA_POSTGRES_PORT'),
            'charset' => 'utf8',
        ]
    ],
    'version_order' => 'creation'
];
