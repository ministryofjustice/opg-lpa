<?php
namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\Lpa\AbstractData;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class Document extends AbstractData {

    const LPA_TYPE_PF = 'property-and-financial';
    const LPA_TYPE_HW = 'health-and-welfare';

    //---

    protected $type;

    protected $donor;

    protected $whoIsRegistering;

    protected $howAreDecisionsMade; // same for both types.

    protected $correspondent;

    protected $instruction;

    protected $preference;

    protected $primaryAttorneys = array();

    protected $replacementAttorneys = array();

    protected $certificateProviders = array();

    protected $peopleToNotify = array();

    //-----------------------------

    public function __construct(){
        parent::__construct();

        # TEMPORARY TEST DATA ------------

        $this->type = self::LPA_TYPE_HW;

        $this->donor = new Donor();

        //$this->whoIsRegistering =
        //$this->howAreDecisionsMade =

        $this->correspondent = new Correspondence();

        $this->instruction = 'Here are some instructions';
        $this->preference = 'Here are some preferences';

        $this->primaryAttorneys[] = new Attorneys\Human();

        $this->replacementAttorneys[] = new Attorneys\TrustCorporation();

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['donor'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Document\Donor' ),
            ]);
        };


    } // function

} // class
