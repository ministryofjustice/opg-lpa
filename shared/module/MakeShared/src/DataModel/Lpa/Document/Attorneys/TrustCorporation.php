<?php

namespace MakeShared\DataModel\Lpa\Document\Attorneys;

use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a Trust Corporation Attorney.
 *
 * Class TrustCorporation
 * @package MakeShared\DataModel\Lpa\Document\Attorneys
 */
class TrustCorporation extends AbstractAttorney
{
    /**
     * Field length constants
     */
    private const NAME_MAX_LENGTH = 75;
    private const NUMBER_MAX_LENGTH = 75;

    /**
     * @var string The company name.
     */
    protected $name;

    /**
     * @var string The company number.
     */
    protected $number;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank(),
            new Assert\Type('string'),
            new Assert\Length(
                max: self::NAME_MAX_LENGTH,
            ),
        ]);

        $metadata->addPropertyConstraints('number', [
            new Assert\NotBlank(),
            new Assert\Type('string'),
            new Assert\Length(
                max: self::NUMBER_MAX_LENGTH,
            ),
        ]);
    }

    public function toArray(bool $retainDateTimeInstances = false)
    {
        return array_merge(parent::toArray($retainDateTimeInstances), [
            'type' => 'trust'
        ]);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): TrustCorporation
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @param string $number
     * @return $this
     */
    public function setNumber(string $number): TrustCorporation
    {
        $this->number = $number;

        return $this;
    }
}
