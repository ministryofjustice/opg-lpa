<?php

declare(strict_types=1);

namespace Application\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppFiltersExtension extends AbstractExtension
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('ordinal_suffix', [$this, 'ordinalSuffix']),
            new TwigFilter('asset_path', [$this, 'assetPath']),
        ];
    }

    public function ordinalSuffix(int $number): string
    {
        $num = $number % 100;

        if ($num < 11 || $num > 13) {
            switch ($num % 10) {
                case 1:
                    return $number . 'st';
                case 2:
                    return $number . 'nd';
                case 3:
                    return $number . 'rd';
            }
        }

        return $number . 'th';
    }

    public function assetPath($path, array $options = []): string
    {
        $path = str_replace('/assets/', "/assets/{$this->config['version']['cache']}/", $path);

        // Should '.min' be include before the file extension.
        if (isset($options['minify']) && $options['minify'] === true) {
            $lastDot = strrpos($path, '.');
            $path = substr($path, 0, $lastDot) . '.min' . substr($path, $lastDot);
        }

        return $path;
    }
}
