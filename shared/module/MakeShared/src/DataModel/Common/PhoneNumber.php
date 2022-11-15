<?php

namespace MakeShared\DataModel\Common;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a phone number.
 *
 * Class PhoneNumber
 * @package MakeShared\DataModel\Common
 */
class PhoneNumber extends AbstractData
{
    /**
     * @var string A phone number.
     */
    protected $number;
}
