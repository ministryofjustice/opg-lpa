<?php
namespace Opg\Lpa\DataModel\Lpa\Document;


use Opg\Lpa\DataModel\Lpa\AbstractData;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys;
use Opg\Lpa\DataModel\Lpa\Document\Decisions;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

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

    //-----------------------------

    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Type mappers

        $this->typeMap['donor'] = function($v){
            return ($v instanceof Donor) ? $v : new Donor( $v );
        };

        $this->typeMap['primaryAttorneyDecisions'] = function($v){
            return ($v instanceof Decisions\PrimaryAttorneyDecisions) ? $v : new Decisions\PrimaryAttorneyDecisions( $v );
        };

        $this->typeMap['replacementAttorneyDecisions'] = function($v){
            return ($v instanceof Decisions\ReplacementAttorneyDecisions) ? $v : new Decisions\ReplacementAttorneyDecisions( $v );
        };

        $this->typeMap['correspondent'] = function($v){
            return ($v instanceof Correspondence) ? $v : new Correspondence( $v );
        };

        $this->typeMap['certificateProvider'] = function($v){
            return ($v instanceof CertificateProvider) ? $v : new CertificateProvider( $v );
        };

        $this->typeMap['primaryAttorneys'] = $this->typeMap['replacementAttorneys'] = function($v){
            return array_map( function($v){
                if( $v instanceof Attorneys\AbstractAttorney ){
                    return $v;
                } elseif( isset( $v['number'] ) ){
                    return new Attorneys\TrustCorporation( $v );
                } else {
                    return new Attorneys\Human( $v );
                }
            }, $v );
        };

        $this->typeMap['peopleToNotify'] = function($v){
            return array_map( function($v){
                return ($v instanceof NotifiedPerson) ? $v : new NotifiedPerson( $v );
            }, $v );
        };


        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['type'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\In( [ self::LPA_TYPE_PF, self::LPA_TYPE_HW ], true ),
            ]);
        };

        $this->validators['donor'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Document\Donor' ),
                new Rules\NullValue,
            ]));
        };

        $this->validators['whoIsRegistering'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                (new Rules\AllOf)->addRules([
                    new Rules\String,
                    new Rules\Equals('donor', true)
                ]),
                new Rules\Arr,
                new Rules\NullValue,
            ]));
        };

        $this->validators['primaryAttorneyDecisions'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions' ),
                new Rules\NullValue,
            ]));
        };

        $this->validators['replacementAttorneyDecisions'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions' ),
                new Rules\NullValue,
            ]));
        };

        $this->validators['correspondent'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Document\Correspondence' ),
                new Rules\NullValue,
            ]));
        };

        $this->validators['instruction'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\String,
                new Rules\NullValue,
            ]));
        };

        $this->validators['preference'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\String,
                new Rules\NullValue,
            ]));
        };

        $this->validators['certificateProvider'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Document\CertificateProvider' ),
                new Rules\NullValue,
            ]));
        };

        $this->validators['primaryAttorneys'] = function(){
            return (new Validator)->addRules([
                new Rules\Arr,
                new Rules\Each(
                    new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney' )
                ),
            ]);
        };

        $this->validators['replacementAttorneys'] = function(){
            return (new Validator)->addRules([
                new Rules\Arr,
                new Rules\Each(
                    new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney' )
                ),
            ]);
        };

        $this->validators['peopleToNotify'] = function(){
            return (new Validator)->addRules([
                new Rules\Arr,
                new Rules\Each(
                    new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson' )
                ),
            ]);
        };

        //---

        parent::__construct( $data );

    } // function

} // class
