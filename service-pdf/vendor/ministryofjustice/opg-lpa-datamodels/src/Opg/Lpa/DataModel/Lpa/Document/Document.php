<?php
namespace Opg\Lpa\DataModel\Lpa\Document;

use stdClass;

use Opg\Lpa\DataModel\Lpa\AbstractData;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class Document extends AbstractData {

    const LPA_TYPE_PF = 'property-and-financial';
    const LPA_TYPE_HW = 'health-and-welfare';

    const LPA_DECISION_MIXED = 'mixed';
    const LPA_DECISION_JOINTLY = 'jointly';
    const LPA_DECISION_SINGLE_ATTORNEY = 'single-attorney';
    const LPA_DECISION_JOINTLY_AND_SEVERALLY = 'jointly-attorney-severally';

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
     * How the decisions are made. One of the constants under Document::LPA_DECISION_*
     *
     * @var stdClass
     */
    protected $decisions;

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

        // Init this property. Maybe create a Decisions class?
        $this->decisions = (object)[ 'how'=>null, 'when'=>null, 'can-sustain-life'=>null ];

        //-----------------------------------------------------
        // Type mappers

        $this->typeMap['donor'] = function($v){
            return ($v instanceof Donor) ? $v : new Donor( $v );
        };

        $this->typeMap['decisions'] = function($v){
            return ($v instanceof stdClass) ? $v : (object)$v;
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

        $this->validators['donor'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Document\Donor' ),
            ]);
        };

        //---

        parent::__construct( $data );

    } // function

} // class
