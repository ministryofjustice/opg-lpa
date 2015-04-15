<?php
namespace Opg\Lpa\DataModel\Lpa\Document;


use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys;
use Opg\Lpa\DataModel\Lpa\Document\Decisions;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;

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
     * If array, it contains a reference to one or more primary attorneys.
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


        $metadata->addPropertyConstraint('whoIsRegistering', new Assert\Callback(function ($value, ExecutionContextInterface $context){

            if( is_null($value) || $value == 'donor' ){ return; }

            //---

            $validAttorneyIds = array_map(function($v){
                return $v->id;
            }, $context->getObject()->primaryAttorneys);

            //---

            // If it's an array, ensure the IDs are valid primary attorney IDs.
            if( is_array($value) && !empty($value) ){

                foreach( $value as $attorneyId ){
                    if( !in_array( $attorneyId, $validAttorneyIds ) ){
                        $context->buildViolation( 'allowed-values:'.implode(',', $validAttorneyIds) )
                            ->setInvalidValue( implode(',', $value) )
                            ->addViolation();
                        return;
                    }
                }

                return;
            }

            $context->buildViolation( 'allowed-values:donor,Array' )->addViolation();

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

        // instruction should be string or boolean false.
        $metadata->addPropertyConstraint('instruction', new Assert\Callback(function ($value, ExecutionContextInterface $context){
            if( is_null($value) || is_string($value) || $value === false ){ return; }
            $context->buildViolation( 'expected-type:string-or-bool=false' )->addViolation();
        }));

        // preference should be string or boolean false.
        $metadata->addPropertyConstraint('preference', new Assert\Callback(function ($value, ExecutionContextInterface $context){
            if( is_null($value) || is_string($value) || $value === false ){ return; }
            $context->buildViolation( 'expected-type:string-or-bool=false' )->addViolation();
        }));

        $metadata->addPropertyConstraints('certificateProvider', [
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Document\CertificateProvider' ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('primaryAttorneys', [
            new Assert\NotNull,
            new Assert\Type([ 'type' => 'array' ]),
            new Assert\All([
                'constraints' => [
                    new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney' ]),
                ]
            ]),
            new Assert\Custom\UniqueIdInArray,
        ]);

        $metadata->addPropertyConstraints('replacementAttorneys', [
            new Assert\NotNull,
            new Assert\Type([ 'type' => 'array' ]),
            new Assert\All([
                'constraints' => [
                    new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney' ]),
                ]
            ]),
            new Assert\Custom\UniqueIdInArray,
        ]);

        // Allow only N trust corporation(s) across primaryAttorneys and replacementAttorneys.
        $metadata->addConstraint( new Assert\Callback(function ($object, ExecutionContextInterface $context){

            $max = 1;
            $attorneys = array_merge( $object->primaryAttorneys, $object->replacementAttorneys );

            $attorneys = array_filter( $attorneys, function($attorney) {
                return $attorney instanceof Attorneys\TrustCorporation;
            });

            if( count($attorneys) > $max ){
                $context->buildViolation( "must-be-less-than-or-equal:{$max}" )
                    ->setInvalidValue( count($attorneys) . " found" )
                    ->atPath('primaryAttorneys/replacementAttorneys')->addViolation();
            }

        }));

        $metadata->addPropertyConstraints('peopleToNotify', [
            new Assert\NotNull,
            new Assert\Type([ 'type' => 'array' ]),
            new Assert\Count( [ 'max' => 5 ] ),
            new Assert\All([
                'constraints' => [
                    new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson' ]),
                ]
            ]),
            new Assert\Custom\UniqueIdInArray,
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
                    } else {
                        return Attorneys\AbstractAttorney::factory( $v );
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

    /**
     * Get primary attorney object by attorney id.
     * 
     * @param int $id
     * @return NULL|AbstractAttorney
     */
    public function getPrimaryAttorneyById($id)
    {
        if($this->primaryAttorneys == null) return null;
    
        foreach($this->primaryAttorneys as $attorney) {
            if($attorney->id == $id) {
                return $attorney;
            }
        }
    
        return null;
    } // function
    
    /**
     * Get replacement attorney object by attorney id.
     * 
     * @param int $id
     * @return NULL|AbstractAttorney
     */
    public function getReplacementAttorneyById($id)
    {
        if($this->replacementAttorneys == null) return null;
    
        foreach($this->replacementAttorneys as $attorney) {
            if($attorney->id == $id) {
                return $attorney;
            }
        }
    
        return null;
    } // function
    
} // class
