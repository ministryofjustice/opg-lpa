<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Elements;

use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class Dob extends AbstractData {

    protected $date;

    public function __construct(){
        parent::__construct();

        # TEMPORARY TEST DATA ------------

        $this->date = new \DateTime( '1980-12-17' );

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['date'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'DateTime' ),
                new Rules\Call(function($input){
                    return ( $input instanceof \DateTime ) ? $input->gettimezone()->getName() : 'UTC';
                }),
            ]);
        };

    } // function

} // class
