<?php

declare(strict_types=1);

namespace Application\View\Helper\Traits;

use function floor;
use function is_numeric;
use function number_format;

trait MoneyFormatterTrait
{
    protected function formatMoney(mixed $amount): string
    {
        if (!is_numeric($amount)) {
            return (string) $amount;
        }

        $amount = (float) $amount;

        if (floor($amount) != $amount) {
            return number_format($amount, 2, '.', ',');
        }

        return number_format($amount, 0, '.', ',');
    }
}
