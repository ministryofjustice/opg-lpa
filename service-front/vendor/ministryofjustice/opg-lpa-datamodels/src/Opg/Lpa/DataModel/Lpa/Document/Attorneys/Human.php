<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

/**
 * Represents a Human Attorney.
 *
 * Class Human
 * @package Opg\Lpa\DataModel\Lpa\Document\Attorney
 */
class Human extends AbstractAttorney {

    /**
     * @var Elements\Name Their name.
     */
    protected $name;

    /**
     * @var Elements\Dob Their date of birth.
     */
    protected $dob;



    public function __construct( $data ){

        //-----------------------------------------------------
        // Type mappers

        $this->typeMap['name'] = function($v){
            return ($v instanceof Elements\Name) ? $v : new Elements\Name( $v );
        };

        $this->typeMap['dob'] = function($v){
            return ($v instanceof Elements\Dob) ? $v : new Elements\Dob( $v );
        };

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['name'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\Name' ),
            ]);
        };

        $this->validators['dob'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\Dob' ),
            ]);
        };

        //---

        parent::__construct( $data );

    } // function

    public function toArray(){

        return array_merge( parent::toArray(), [ 'type'=>'human' ] );

    }

} // class
