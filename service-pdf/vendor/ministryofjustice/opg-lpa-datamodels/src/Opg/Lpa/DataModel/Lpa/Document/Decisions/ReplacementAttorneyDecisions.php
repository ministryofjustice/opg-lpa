<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Decisions;

use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class ReplacementAttorneyDecisions extends AbstractDecisions {

    const LPA_DECISION_WHEN_FIRST = 'first';
    const LPA_DECISION_WHEN_LAST = 'last';
    const LPA_DECISION_WHEN_DEPENDS = 'depends';


    /**
     * @var string Additional free text details relating to this decision.
     */
    protected $whenDetails;

    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['when'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\In( [
                    self::LPA_DECISION_WHEN_FIRST,
                    self::LPA_DECISION_WHEN_LAST,
                    self::LPA_DECISION_WHEN_DEPENDS
                ], true ),
            ]);
        };

        $this->validators['whenDetails'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                (new Rules\AllOf)->addRules([
                    new Rules\String,
                    new Rules\Length( 1, (1000*1024), true ),
                ]),
                new Rules\NullValue,
            ]));
        };

        //---

        parent::__construct( $data );

    } // function


} // class
