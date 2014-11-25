<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Decisions;

use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class PrimaryAttorneyDecisions extends AbstractDecisions {

    const LPA_DECISION_WHEN_NOW = 'now';
    const LPA_DECISION_WHEN_NO_CAPACITY = 'no-capacity';

    /**
     * @var bool Represents whether this LPA allows Attorneys to make life sustaining decisions. True = yes; false = no.
     */
    protected $canSustainLife;


    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)


        $this->validators['when'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\In( [
                    self::LPA_DECISION_WHEN_NOW,
                    self::LPA_DECISION_WHEN_NO_CAPACITY
                ], true ),
            ]);
        };

        $this->validators['canSustainLife'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Bool,
                new Rules\NullValue,
            ]));
        };

        //---

        parent::__construct( $data );

    } // function


} // class
