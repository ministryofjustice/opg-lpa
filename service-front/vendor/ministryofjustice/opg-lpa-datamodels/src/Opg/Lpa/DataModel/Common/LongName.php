<?php

namespace Opg\Lpa\DataModel\Common;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a person's name.
 *
 * Class Name
 * @package Opg\Lpa\DataModel\Common
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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('title', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'min' => self::TITLE_MIN_LENGTH,
                'max' => self::TITLE_MAX_LENGTH,
            ]),
        ]);

        $metadata->addPropertyConstraints('first', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'max' => self::FIRST_NAME_MAX_LENGTH,
            ]),
        ]);

        $metadata->addPropertyConstraints('last', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'max' => self::LAST_NAME_MAX_LENGTH,
            ]),
        ]);
    }

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
    public function setTitle(string $title): LongName
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
    public function setFirst(string $first): LongName
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
    public function setLast(string $last): LongName
    {
        $this->last = $last;

        return $this;
    }
}
