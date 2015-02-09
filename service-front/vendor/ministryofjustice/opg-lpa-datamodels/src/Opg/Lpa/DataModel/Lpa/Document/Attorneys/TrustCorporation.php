<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Elements;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;

/**
 * Represents a Trust Corporation Attorney.
 *
 * Class TrustCorporation
 * @package Opg\Lpa\DataModel\Lpa\Document\Attorneys
 */
class TrustCorporation extends AbstractAttorney {

    /**
     * @var string The company name,
     */
    protected $name;

    /**
     * @var string The company number.
     */
    protected $number;

    //------------------------------------------------

    public static function loadValidatorMetadata(ClassMetadata $metadata){

        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank,
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Length([ 'min' => 1, 'max' => 75 ]),
        ]);

        $metadata->addPropertyConstraints('number', [
            new Assert\NotBlank,
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Length([ 'min' => 1, 'max' => 75 ]),
        ]);

    } // function

    //------------------------------------------------

    public function toArray(){

        return array_merge( parent::toArray(), [ 'type'=>'trust' ] );

    }

} // class
