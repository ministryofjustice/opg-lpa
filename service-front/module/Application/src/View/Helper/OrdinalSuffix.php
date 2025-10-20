<?php

namespace Application\View\Helper;

use NumberFormatter;
use Laminas\View\Helper\AbstractHelper;

class OrdinalSuffix extends AbstractHelper
{
    public function __invoke($number)
    {

        if (!is_int($number)) {
            throw new \InvalidArgumentException('Passed value must be an integer');
        }

        $num = $number % 100; // protect against large numbers
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
