<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Decisions;

use Opg\Lpa\DataModel\Lpa\Elements;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;

class PrimaryAttorneyDecisions extends AbstractDecisions {

    const LPA_DECISION_WHEN_NOW = 'now';
    const LPA_DECISION_WHEN_NO_CAPACITY = 'no-capacity';

    /**
     * @var bool Represents whether this LPA allows Attorneys to make life sustaining decisions. True = yes; false = no.
     */
    protected $canSustainLife;

    //------------------------------------------------

    public static function loadValidatorMetadata(ClassMetadata $metadata){

        $metadata->addPropertyConstraints('when', [
            // Can be null
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Choice([ 'choices' => [
                self::LPA_DECISION_WHEN_NOW,
                self::LPA_DECISION_WHEN_NO_CAPACITY
            ] ]),
        ]);

        $metadata->addPropertyConstraints('canSustainLife', [
            // Can be null
            new Assert\Type([ 'type' => 'bool' ]),
        ]);

    }

} // class
