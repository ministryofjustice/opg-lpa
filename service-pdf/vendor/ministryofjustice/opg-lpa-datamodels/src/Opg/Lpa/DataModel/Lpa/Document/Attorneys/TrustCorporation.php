<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

/**
 * Represents a Trust Corporation Attorney.
 *
 * Class TrustCorporation
 * @package Opg\Lpa\DataModel\Lpa\Document\Attorneys
 */
class TrustCorporation extends AbstractAttorney {

    /**
     * @var string The company name,
     */
    protected $name;

    /**
     * @var string The company number.
     */
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

    public function toArray(){

        return array_merge( parent::toArray(), [ 'type'=>'trust' ] );

    }

}
