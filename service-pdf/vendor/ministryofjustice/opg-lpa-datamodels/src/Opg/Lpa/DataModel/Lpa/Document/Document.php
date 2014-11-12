<?php
namespace Opg\Lpa\DataModel\Lpa\Document;

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
     * @var string
     */
    protected $howAreDecisionsMade; // same for both types.

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
     * All of the Certificate Providers.
     *
     * @var array containing instances of CertificateProvider.
     */
    protected $certificateProviders = array();

    /**
     * All of the people to notify.
     *
     * @var array containing instances of NotifiedPerson.
     */
    protected $peopleToNotify = array();

    //-----------------------------

    public function __construct(){
        parent::__construct();

        # TEMPORARY TEST DATA ------------

        $this->type = self::LPA_TYPE_HW;

        $this->donor = new Donor();

        $this->whoIsRegistering = 'donor';

        $this->howAreDecisionsMade = self::LPA_DECISION_SINGLE_ATTORNEY;

        $this->correspondent = new Correspondence();

        $this->instruction = 'Here are some instructions';

        $this->preference = 'Here are some preferences';

        $this->primaryAttorneys[] = new Attorneys\Human();

        $this->replacementAttorneys[] = new Attorneys\TrustCorporation();

        $this->certificateProviders = new CertificateProvider();

        $this->peopleToNotify[] = new NotifiedPerson();

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['donor'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Document\Donor' ),
            ]);
        };


    } // function

} // class
