<?php
namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\Lpa\AbstractData;
use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

/**
 * Represents the Donor associated with the LPA.
 *
 * Class Donor
 * @package Opg\Lpa\DataModel\Lpa\Document
 */
class Donor extends AbstractData {

    /**
     * @var Elements\Name Their name.
     */
    protected $name;

    /**
     * @var string Any other/past names they've may be known as.
     */
    protected $otherNames;

    /**
     * @var Elements\Address Their postal address.
     */
    protected $address;

    /**
     * @var Elements\Dob Their date of birth.
     */
    protected $dob;

    /**
     * @var Elements\EmailAddress Their email address.
     */
    protected $email;



    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Type mappers

        $this->typeMap['name'] = function($v){
            return ($v instanceof Elements\Name) ? $v : new Elements\Name( $v );
        };

        $this->typeMap['address'] = function($v){
            return ($v instanceof Elements\Address) ? $v : new Elements\Address( $v );
        };

        $this->typeMap['dob'] = function($v){
            return ($v instanceof Elements\Dob) ? $v : new Elements\Dob( $v );
        };

        $this->typeMap['email'] = function($v){
            return ($v instanceof Elements\EmailAddress) ? $v : new Elements\EmailAddress( $v );
        };

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['name'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\Name' ),
            ]);
        };

        $this->validators['otherNames'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                (new Rules\AllOf)->addRules([
                    new Rules\String,
                    new Rules\NotEmpty,
                    new Rules\Length( 1, 50, true ),
                ]),
                new Rules\NullValue,
            ]));
        };


        $this->validators['address'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\Address' ),
            ]);
        };

        $this->validators['dob'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\Dob' ),
            ]);
        };

        $this->validators['email'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\EmailAddress' ),
                new Rules\NullValue,
            ]));
        };

        //---

        parent::__construct( $data );

    } // function

} // class
