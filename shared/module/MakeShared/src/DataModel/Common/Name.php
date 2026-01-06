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
class Name extends AbstractData
{
    /**
     * Field length constants
     */
    public const TITLE_MIN_LENGTH = 1;
    public const TITLE_MAX_LENGTH = 5;
    public const FIRST_NAME_MAX_LENGTH = 50; //32;
    public const LAST_NAME_MAX_LENGTH = 50;//40;

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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('title', [
            new Assert\NotIdenticalTo(''),  // Not identical to en empty string
            new Assert\Type('string'),
            new Assert\Length(
                min: self::TITLE_MIN_LENGTH,
                max: self::TITLE_MAX_LENGTH,
            ),
        ]);

        $metadata->addPropertyConstraints('first', [
            new Assert\NotBlank(),
            new Assert\Type('string'),
            new Assert\Length(
                max: self::FIRST_NAME_MAX_LENGTH,
            ),
        ]);

        $metadata->addPropertyConstraints('last', [
            new Assert\NotBlank(),
            new Assert\Type('string'),
            new Assert\Length(
                max: self::LAST_NAME_MAX_LENGTH,
            ),
        ]);
    }

    /**
     * Returns a string representation of the name.
     *
     * @return string
     */
    public function __toString(): string
    {
        $name  = (!empty($this->title) ? "{$this->title} " : '');
        $name .= (!empty($this->first) ? "{$this->first} " : '');
        $name .= (!empty($this->last)  ? "{$this->last}" : '');

        // Tidy the string up...
        $name = trim($name);

        return $name;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): Name
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirst(): string
    {
        return $this->first;
    }

    /**
     * @param string $first
     * @return $this
     */
    public function setFirst(string $first): Name
    {
        $this->first = $first;

        return $this;
    }

    /**
     * @return string
     */
    public function getLast(): string
    {
        return $this->last;
    }

    /**
     * @param string $last
     * @return $this
     */
    public function setLast(string $last): Name
    {
        $this->last = $last;

        return $this;
    }
}
