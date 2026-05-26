<?php

declare(strict_types=1);

namespace App;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.laminas.dev/laminas-component-installer/
 */
class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'templates' => $this->getTemplates(),
            'twig'      => $this->getTwig(),
            'version'   => [
                'tag'   => getenv('OPG_LPA_COMMON_APP_VERSION') ?: 'dev',
                'cache' => getenv('OPG_LPA_COMMON_ASSETS_VERSION') ?: '',
            ],
        ];
    }

    public function getTemplates(): array
    {
        return [
            'paths' => [
                'app'         => [__DIR__ . '/../templates/app'],
                'error'       => [__DIR__ . '/../templates/error'],
                'layout'      => [__DIR__ . '/../templates/layout'],
                'application' => [__DIR__ . '/../templates/application'],
                __DIR__ . '/../templates',
            ],
        ];
    }

    public function getTwig(): array
    {
        return [
            'strict_variables' => false,
            'extensions' => [
                View\Twig\LegacyCompatExtension::class,
            ],
        ];
    }
}
