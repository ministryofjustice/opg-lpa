<?php

namespace MakeShared\DataModel\Common;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a person's name.
 *
 * Class Name
 * @package MakeShared\DataModel\Common
 */
class LongName extends AbstractData
{
    /**
     * Field length constants
     */
    const TITLE_MIN_LENGTH = 1;
    const TITLE_MAX_LENGTH = 5;
    const FIRST_NAME_MAX_LENGTH = 53;
    const LAST_NAME_MAX_LENGTH = 61;

    /**
     * @var string A person's title. E.g. Mr, Miss, Mrs, etc.
     */
    protected $title;

    /**
     * @var string A person's first name (or names).
     */
    protected $first;

    /**
     * @var string A person's last name.
     */
    protected $last;

    /**
     * Returns a string representation of the name.
     *
     * @return string
     */
    public function __toString()
    {
        $name  = (!empty($this->title) ? "{$this->title} " : '');
        $name .= (!empty($this->first) ? "{$this->first} " : '');
        $name .= (!empty($this->last)  ? "{$this->last}" : '');

        // Tidy the string up...
        $name = trim($name);

        return $name;
    }
}
