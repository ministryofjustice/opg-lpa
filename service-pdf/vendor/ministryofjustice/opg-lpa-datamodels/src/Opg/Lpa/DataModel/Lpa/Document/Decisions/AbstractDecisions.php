<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Decisions;

use Opg\Lpa\DataModel\Lpa\AbstractData;
use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

abstract class AbstractDecisions extends AbstractData {

    const LPA_DECISION_HOW_DEPENDS = 'depends';
    const LPA_DECISION_HOW_JOINTLY = 'jointly';
    const LPA_DECISION_HOW_SINGLE_ATTORNEY = 'single-attorney';
    const LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY = 'jointly-attorney-severally';

    const LPA_DECISION_WHEN_NOW = 'now';
    const LPA_DECISION_WHEN_NO_CAPACITY = 'no-capacity';

    /**
     * @var string Represents how decisions will be made.
     */
    protected $how;

    /**
     * Validators for 'when' should be in concrete classes.
     *
     * @var string Represents when decisions can be made.
     */
    protected $when;

    /**
     * @var string Additional free text details relating to this 'how' decision.
     */
    protected $howDetails;


    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['how'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\In([
                    self::LPA_DECISION_HOW_DEPENDS,
                    self::LPA_DECISION_HOW_JOINTLY,
                    self::LPA_DECISION_HOW_SINGLE_ATTORNEY,
                    self::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
                ], true ),
            ]);
        };

        $this->validators['howDetails'] = function(){
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

} // abstract class
