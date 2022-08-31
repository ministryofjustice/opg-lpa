<?php

namespace App\Filter;

use Laminas\Filter;

/**
 * Class StandardInput
 * @package App\Filter
 */
class StandardInputFilterChain
{
    /**
     * Create the standard filter chain applied to inputs.
     */
    public static function create()
    {
        $chain = new Filter\FilterChain();
        $chain->attach(new Filter\StringTrim());
        $chain->attach(new Filter\StripTags());
        $chain->attach(new Filter\StripNewlines());
        return $chain;
    }
}
