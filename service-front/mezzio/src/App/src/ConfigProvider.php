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
                'tag'   => getenv('OPG_DOCKER_TAG') ?: 'dev',
                'cache' => (getenv('OPG_DOCKER_TAG') !== false) ? abs(crc32(getenv('OPG_DOCKER_TAG'))) : '',
            ],
            'redirects' => [
                'index'  => 'https://www.gov.uk/power-of-attorney/make-lasting-power',
                'logout' => 'https://www.gov.uk/done/lasting-power-of-attorney',
            ],
            'terms' => [
                'lastUpdated' => '2015-02-17 14:00 UTC',
            ],
        ];
    }

    public function getTemplates(): array
    {
        return [
            'paths' => [
                'error'       => [__DIR__ . '/../templates/error'],
                'layout'      => [__DIR__ . '/../templates/layout'],
                'application' => [__DIR__ . '/../templates/application'],
                'guidance'    => [__DIR__ . '/../templates/guidance'],
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
