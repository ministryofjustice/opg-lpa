<?php

declare(strict_types=1);

namespace Application\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppFiltersExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('ordinal_suffix', [$this, 'ordinalSuffix']),
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
}
