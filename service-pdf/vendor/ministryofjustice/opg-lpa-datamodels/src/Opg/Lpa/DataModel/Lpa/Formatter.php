<?php

namespace Opg\Lpa\DataModel\Lpa;

use InvalidArgumentException;

/**
 * Static classes used for formatting values, ready for the end user.
 *
 * Class Formatter
 * @package Opg\Lpa\DataModel\Lpa
 */
class Formatter
{
    /**
     * Formats the id as an A, followed by 11 digits, split into 3 blocks of 4 characters.
     *
     * For example: 'A000 1234 5678'
     *
     * @param int $value The LPA's id.
     * @return string The formatted value.
     */
    public static function id($value)
    {
        if (!is_int($value)) {
            throw new InvalidArgumentException('The passed value must be an integer.');
        }

        return trim(chunk_split('A' . sprintf("%011d", $value), 4, ' '));
    }
}
