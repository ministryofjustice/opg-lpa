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
    const NAME_MAX_LENGTH = 75;
    const NUMBER_MAX_LENGTH = 75;

    /**
     * @var string The company name.
     */
    protected $name;

    /**
     * @var string The company number.
     */
    protected $number;

    /**
     * @return void
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank(),
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'max' => self::NAME_MAX_LENGTH,
            ]),
        ]);

        $metadata->addPropertyConstraints('number', [
            new Assert\NotBlank(),
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'max' => self::NUMBER_MAX_LENGTH,
            ]),
        ]);
    }

    /**
     * @return (mixed|string)[]
     *
     * @psalm-return array{type: 'trust'}
     */
    public function toArray(bool $retainDateTimeInstances = false)
    {
        return array_merge(parent::toArray($retainDateTimeInstances), [
            'type' => 'trust'
        ]);
    }
}
