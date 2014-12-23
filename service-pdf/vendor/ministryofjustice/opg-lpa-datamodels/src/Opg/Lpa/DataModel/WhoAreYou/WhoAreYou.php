<?php
namespace Opg\Lpa\DataModel\WHoAreYou;

use Opg\Lpa\DataModel\AbstractData;
use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

/**
 * Represents a response to the 'Who are you?' question.
 *
 * Class WhoAreYou
 * @package Opg\Lpa\DataModel\Lpa
 */
class WhoAreYou extends AbstractData {

    /**
     * @var string Answer to the top level of options.
     */
    protected $who;

    /**
     * @var string|null Answer to the second level question.
     */
    protected $subquestion;

    /**
     * @var string|null Extra details explaining their sub-question answer.
     */
    protected $qualifier;


    //------------------------------------------------

    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['who'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\In( array_keys( self::options() ), true ),
            ]);
        };

        /*
         * Checks the the value matches one listed in options().
         */
        $this->validators['subquestion'] = function(){
            return (new Validator)->addRules([
                new Rules\Callback(function($input) {

                    if( !is_string($this->who) ){ return false; }

                    $options = $this::options();

                    if( !isset($options[$this->who]) ){ return false; }

                    return in_array( $input, $options[$this->who]['subquestion'], true );

                })
            ]);
        };

        /*
         * Checks options() to see if a qualifier is allowed.
         */
        $this->validators['qualifier'] = function(){
            return (new Validator)->addRules([
                new Rules\Callback(function($input) {

                    if( !is_string($this->who) ){ return false; }

                    $options = $this::options();

                    if( !isset($options[$this->who]) ){ return false; }

                    // A qualifier is optional, so only invalid if a qualifier is not allowed, but one is set.
                    if( $options[$this->who]['qualifier'] == false && !is_null($input) ){
                        return false;
                    }

                    return true;
                })
            ]);
        };

        //---

        parent::__construct( $data );

    } // function

    /**
     * @return array An array representing the valid option.
     */
    public static function options(){

        return array(
            'professional' => [
                'subquestion' => [ 'solicitor', 'will-writer', 'other' ],
                'qualifier' => true,
            ],
            'digitalPartner' => [
                'subquestion' => ['Age-Uk', 'Alzheimer-Society', 'Citizens-Advice-Bureau' ],
                'qualifier' => false,
            ],
            'organisation' => [
                'subquestion' => [ null ],
                'qualifier' => true,
            ],
            'donor' => [
                'subquestion' => [ null ],
                'qualifier' => false,
            ],
            'friendOrFamily' => [
                'subquestion' => [ null ],
                'qualifier' => false,
            ],
            'notSaid' => [
                'subquestion' => [ null ],
                'qualifier' => false,
            ],
        );

    }

} // class
