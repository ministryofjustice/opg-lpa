<?php

namespace MakeShared\DataModel\Common;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents an email address.
 *
 * Class EmailAddress
 * @package MakeShared\DataModel\Common
 */
class EmailAddress extends AbstractData
{
    /**
     * @var string An email address.
     */
    protected $address;

    /**
     * @return string The email address.
     */
    public function __toString()
    {
        return $this->address;
    }
}
