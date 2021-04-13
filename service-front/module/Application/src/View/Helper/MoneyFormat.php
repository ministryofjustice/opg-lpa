<?php
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use function number_format;

class MoneyFormat extends AbstractHelper
{
    public function __invoke($amount)
    {
        // If the amount it a round number, just output pounds. Otherwise include pence.
        if (is_numeric($amount) && floor($amount) != $amount) {
            $amount = number_format(floatval($amount), 2, '.', ',');
        }
        return $amount;
    }
}
