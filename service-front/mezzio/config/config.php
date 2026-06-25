<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

// To enable or disable caching, set the `ConfigAggregator::ENABLE_CACHE` boolean in
// `config/autoload/local.php`.
$cacheConfig = [
    'config_cache_path' => 'data/cache/config-cache.php',
];

$aggregator = new ConfigAggregator([
    \Mezzio\Flash\ConfigProvider::class,
    \Laminas\Hydrator\ConfigProvider::class,
    \Laminas\InputFilter\ConfigProvider::class,
    \Laminas\Filter\ConfigProvider::class,
    \Laminas\Validator\ConfigProvider::class,
    \Mezzio\Csrf\ConfigProvider::class,
    \Mezzio\Session\ConfigProvider::class,
    \Mezzio\Twig\ConfigProvider::class,
    \Mezzio\Tooling\ConfigProvider::class,
    \Mezzio\Session\Ext\ConfigProvider::class,
    \Laminas\Form\ConfigProvider::class,
    \Mezzio\Router\FastRouteRouter\ConfigProvider::class,
    \Laminas\HttpHandlerRunner\ConfigProvider::class,
    // Include cache configuration
    new ArrayProvider($cacheConfig),
    \Mezzio\Helper\ConfigProvider::class,
    \Mezzio\ConfigProvider::class,
    \Mezzio\Router\ConfigProvider::class,
    \Laminas\Diactoros\ConfigProvider::class,
    // Swoole config to overwrite some services (if installed)
    class_exists(\Mezzio\Swoole\ConfigProvider::class)
        ? \Mezzio\Swoole\ConfigProvider::class
        : function (): array {
            return [];
        },
    // Default App module config
    App\ConfigProvider::class,
    new PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),
    // Allow dev mode to be enabled via APP_ENV=development or MEZZIO_DEBUG=1 env vars.
    // This is the recommended way to enable dev mode in deployed environments.
    // Overrides mezzio.global.php which enables config caching by default.
    new ArrayProvider(
        (getenv('APP_ENV') === 'development' || getenv('MEZZIO_DEBUG') === '1')
            ? ['debug' => true, ConfigAggregator::ENABLE_CACHE => false]
            : []
    ),
    // Load development config file if it exists (file-based toggle for local dev via
    // `composer development-enable`). This file is gitignored and excluded from Docker
    // images via .dockerignore — it must never be present in production.
    new PhpFileProvider(realpath(__DIR__) . '/development.config.php'),
], $cacheConfig['config_cache_path']);

return $aggregator->getMergedConfig();
