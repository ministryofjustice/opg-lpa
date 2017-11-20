<?php

namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a Trust Corporation Attorney.
 *
 * Class TrustCorporation
 * @package Opg\Lpa\DataModel\Lpa\Document\Attorneys
 */
class TrustCorporation extends AbstractAttorney
{
    /**
     * Field length constants
     */
    const NAME_MIN_LENGTH = 1;
    const NAME_MAX_LENGTH = 40;
    const NUMBER_MIN_LENGTH = 1;
    const NUMBER_MAX_LENGTH = 40;

    /**
     * @var string The company name,
     */
    protected $name;

    /**
     * @var string The company number.
     */
    protected $number;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'min' => self::NAME_MIN_LENGTH,
                'max' => self::NAME_MAX_LENGTH,
            ]),
        ]);

        $metadata->addPropertyConstraints('number', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'min' => self::NUMBER_MIN_LENGTH,
                'max' => self::NUMBER_MAX_LENGTH,
            ]),
        ]);
    }

    public function toArray(callable $dateCallback = null)
    {
        return array_merge(parent::toArray($dateCallback), [
            'type' => 'trust'
        ]);
    }
}
