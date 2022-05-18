<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

use function number_format;

class MoneyFormat extends AbstractHelper
{
    public function __invoke($amount)
    {
        // If the amount is a round number, just output pounds. Otherwise include pence.
        if (!is_numeric($amount)) {
            return $amount;
        }

        $amount = floatval($amount);

        if (floor($amount) != $amount) {
            return number_format($amount, 2, '.', ',');
        }

        return number_format($amount, 0, '.', ',');
    }
}
