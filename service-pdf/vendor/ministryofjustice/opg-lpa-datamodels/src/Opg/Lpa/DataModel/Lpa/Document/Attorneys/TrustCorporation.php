<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class TrustCorporation extends AbstractAttorney {

    protected $name;
    protected $number;

    public function __construct( $data ){

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['name'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\NotEmpty,
                new Rules\Length( 1, 75, true ),
            ]);
        };

        $this->validators['number'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\NotEmpty,
                new Rules\Length( 1, 75, true ),
            ]);
        };

        //---

        parent::__construct( $data );

    } // function

}
