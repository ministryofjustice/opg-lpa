<?php
namespace Opg\Lpa\DataModel\Lpa\Document;


use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys;
use Opg\Lpa\DataModel\Lpa\Document\Decisions;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Document extends AbstractData {

    const LPA_TYPE_PF = 'property-and-financial';
    const LPA_TYPE_HW = 'health-and-welfare';

    //---

    /**
     * The LPA type. One of the constants under Document::LPA_TYPE_*
     *
     * @var string
     */
    protected $type;

    /**
     * @var Donor The donor.
     */
    protected $donor;

    /**
     * If string, it's the donor who is registering.
     * If array, it contains a reference to one or more attorneys.
     *
     * @var string|array
     */
    protected $whoIsRegistering;

    /**
     * How the decisions are made with primary attorney.
     *
     * @var Decisions\PrimaryAttorneyDecisions
     */
    protected $primaryAttorneyDecisions;

    /**
     * How the decisions are made with replacement attorney.
     *
     * @var Decisions\ReplacementAttorneyDecisions
     */
    protected $replacementAttorneyDecisions;

    /**
     * The entity who should receive correspondence about the LPA.
     *
     * @var Correspondence
     */
    protected $correspondent;

    /**
     * Additional instructions to be included on the form.
     *
     * @var string
     */
    protected $instruction;

    /**
     * The Donor's preferences.
     *
     * @var string
     */
    protected $preference;

    /**
     * The Certificate Provider.
     *
     * @var CertificateProvider
     */
    protected $certificateProvider;

    /**
     * All of the primary Attorneys.
     *
     * @var array containing instances of Attorney.
     */
    protected $primaryAttorneys = array();

    /**
     * All of the replacement Attorneys.
     *
     * @var array containing instances of Attorney.
     */
    protected $replacementAttorneys = array();

    /**
     * All of the people to notify.
     *
     * @var array containing instances of NotifiedPerson.
     */
    protected $peopleToNotify = array();

    //------------------------------------------------

    public static function loadValidatorMetadata(ClassMetadata $metadata){

        $metadata->addPropertyConstraints('type', [
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Choice([ 'choices' => [ self::LPA_TYPE_PF, self::LPA_TYPE_HW ] ]),
        ]);

        $metadata->addPropertyConstraints('donor', [
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Document\Donor' ]),
            new Assert\Valid,
        ]);

        // whoIsRegistering should (if set) be either an array, or the string 'donor'.
        $metadata->addPropertyConstraint('whoIsRegistering', new Assert\Callback(function ($value, ExecutionContextInterface $context){

            if( empty($value) || is_array($value) || $value == 'donor' ){ return; }

            $context->buildViolation( (new Assert\Choice())->message )->addViolation();

        }));

        $metadata->addPropertyConstraints('primaryAttorneyDecisions', [
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions' ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('replacementAttorneyDecisions', [
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions' ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('correspondent', [
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Document\Correspondence' ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('instruction', [
            new Assert\Type([ 'type' => 'string' ]),
        ]);

        $metadata->addPropertyConstraints('preference', [
            new Assert\Type([ 'type' => 'string' ]),
        ]);

        $metadata->addPropertyConstraints('certificateProvider', [
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Document\CertificateProvider' ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('primaryAttorneys', [
            new Assert\All([
                'constraints' => [
                    new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney' ]),
                    //new Assert\Valid,
                ]
            ])
        ]);

        $metadata->addPropertyConstraints('replacementAttorneys', [
            new Assert\All([
                'constraints' => [
                    new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney' ]),
                    //new Assert\Valid,
                ]
            ])
        ]);

        $metadata->addPropertyConstraints('peopleToNotify', [
            new Assert\All([
                'constraints' => [
                    new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson' ]),
                    //new Assert\Valid,
                ]
            ])
        ]);

    } // function

    //------------------------------------------------

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $v mixed Value to map.
     * @return mixed Mapped value.
     */
    protected function map( $property, $v ){

        switch( $property ){
            case 'donor':
                return ($v instanceof Donor || is_null($v)) ? $v : new Donor( $v );
            case 'primaryAttorneyDecisions':
                return ($v instanceof Decisions\PrimaryAttorneyDecisions || is_null($v)) ? $v : new Decisions\PrimaryAttorneyDecisions( $v );
            case 'replacementAttorneyDecisions':
                return ($v instanceof Decisions\ReplacementAttorneyDecisions || is_null($v)) ? $v : new Decisions\ReplacementAttorneyDecisions( $v );
            case 'correspondent':
                return ($v instanceof Correspondence || is_null($v)) ? $v : new Correspondence( $v );
            case 'certificateProvider':
                return ($v instanceof CertificateProvider || is_null($v)) ? $v : new CertificateProvider( $v );

            case 'primaryAttorneys':
            case 'replacementAttorneys':
                return array_map( function($v){
                    if( $v instanceof Attorneys\AbstractAttorney){
                        return $v;
                    } elseif( isset( $v['number'] ) ){
                        return new Attorneys\TrustCorporation( $v );
                    } else {
                        return new Attorneys\Human( $v );
                    }
                }, $v );

            case 'peopleToNotify':
                return array_map( function($v){
                    return ($v instanceof NotifiedPerson) ? $v : new NotifiedPerson( $v );
                }, $v );
        }

        // else...
        return parent::map( $property, $v );

    } // function


} // class
