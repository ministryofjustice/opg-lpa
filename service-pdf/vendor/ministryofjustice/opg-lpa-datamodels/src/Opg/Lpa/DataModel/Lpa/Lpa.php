<?php
namespace Opg\Lpa\DataModel\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Document;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator; // Extended instance of Respect\Validation\Validator

class Lpa extends AbstractData implements CompleteInterface {

    /**
     * @var int The LPA identifier.
     */
    protected $id;

    /**
     * @var DateTime the LPA was created.
     */
    protected $createdAt;

    /**
     * @var DateTime the LPA was last updated.
     */
    protected $updatedAt;

    /**
     * @var string LPS's owner User identifier.
     */
    protected $user;

    /**
     * @var Payment status.
     */
    protected $payment;

    /**
     * @var bool Flag to record whether the 'Who Are You' question has been answered with regards to this LPA.
     */
    protected $whoAreYouAnswered;

    /**
     * @var bool Is this LPA locked. i.e. read-only.
     */
    protected $locked;

    /**
     * @var int Reference to another LPA on which this LPA is based.
     */
    protected $seed;

    /**
     * @var Document All the details making up the LPA document.
     */
    protected $document;

    //------------------------------------------------

    public function __construct(){
        parent::__construct();

        # TEMPORARY TEST DATA ------------

        $this->id = 1234;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->user = 'ad353da6b73ceee2201cee2f9936c509';
        $this->payment = null;
        $this->whoAreYouAnswered = false;
        $this->locked = false;
        $this->seed = null;
        $this->document = new Document();

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['id'] = function(){
            return (new Validator)->addRules([
                new Rules\Int,
                new Rules\Between( 0, 99999999999, true ),
            ]);
        };

        $this->validators['createdAt'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'DateTime' ),
                new Rules\Call(function($input){
                    return ( $input instanceof \DateTime ) ? $input->gettimezone()->getName() : 'UTC';
                }),
            ]);
        };

        $this->validators['updatedAt'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'DateTime' ),
                new Rules\Call(function($input){
                    return ( $input instanceof \DateTime ) ? $input->gettimezone()->getName() : 'UTC';
                }),
            ]);
        };

        $this->validators['user'] = function(){
            return (new Validator)->addRules([
                new Rules\NotEmpty,
                new Rules\Xdigit,
                new Rules\Length( 32, 32, true ),
            ]);
        };

        $this->validators['payment'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Payment\Payment' ),
                new Rules\NullValue,
            ]));
        };

        $this->validators['whoAreYouAnswered'] = function(){
            return (new Validator)->addRules([
                new Rules\Bool,
            ]);
        };

        $this->validators['locked'] = function(){
            return (new Validator)->addRules([
                new Rules\Bool,
            ]);
        };

        $this->validators['seed'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Int,
                new Rules\NullValue,
            ]));
        };

        $this->validators['document'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Document\Document' ),
                new Rules\NullValue,
            ]));
        };


    } // function

    //--------------------------------------------------------------------

    /**
     * Check whether the LPA document is complete and valid at the business level.
     *
     * @return bool
     */
    public function isComplete(){

        return true;

    } // function

} // class
