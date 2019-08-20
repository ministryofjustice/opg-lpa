<?php

namespace App\Filter;

use Zend\Filter;

/**
 * Class StandardInput
 * @package App\Filter
 */
class StandardInput extends Filter\FilterChain
{
    /**
     * StandardInput constructor.
     * @param null $options
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        $this->attach(new Filter\StringTrim());
        $this->attach(new Filter\StripTags());
        $this->attach(new Filter\StripNewlines());
    }
}
