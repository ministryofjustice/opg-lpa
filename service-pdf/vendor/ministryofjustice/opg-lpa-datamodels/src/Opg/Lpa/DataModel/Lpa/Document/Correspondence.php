<?php
namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\Lpa\AbstractData;
use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

/**
 * Represents the person with whom Correspondence relating to the LPA should be sent.
 *
 * Class Correspondence
 * @package Opg\Lpa\DataModel\Lpa\Document
 */
class Correspondence extends AbstractData {

    const WHO_DONOR = 'donor';
    const WHO_ATTORNEY = 'attorney';
    const WHO_OTHER = 'other';

    /**
     * @var string The person's role within this LPA.
     */
    protected $who;

    /**
     * @var Elements\Name Their name.
     */
    protected $name;

    /**
     * @var string Their company name.
     */
    protected $company;

    /**
     * @var Elements\Address Their postal address.
     */
    protected $address;

    /**
     * @var Elements\EmailAddress Their email address.
     */
    protected $email;

    /**
     * @var Elements\PhoneNumber Their phone number.
     */
    protected $phone;



    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Type mappers

        $this->typeMap['name'] = function($v){
            return ($v instanceof Elements\Name) ? $v : new Elements\Name( $v );
        };

        $this->typeMap['address'] = function($v){
            return ($v instanceof Elements\Address) ? $v : new Elements\Address( $v );
        };

        $this->typeMap['email'] = function($v){
            return ($v instanceof Elements\EmailAddress) ? $v : new Elements\EmailAddress( $v );
        };

        $this->typeMap['phone'] = function($v){
            return ($v instanceof Elements\PhoneNumber) ? $v : new Elements\PhoneNumber( $v );
        };


        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['who'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\In( [ self::WHO_DONOR, self::WHO_ATTORNEY, self::WHO_OTHER ], true ),
            ]);
        };

        $this->validators['name'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\Name' ),
            ]);
        };

        $this->validators['company'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                (new Rules\AllOf)->addRules([
                    new Rules\String,
                    new Rules\NotEmpty,
                    new Rules\Length( 1, 75, true ),
                ]),
                new Rules\NullValue,
            ]));
        };

        $this->validators['address'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\Address' ),
            ]);
        };

        $this->validators['email'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\EmailAddress' ),
                new Rules\NullValue,
            ]));
        };

        $this->validators['phone'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\PhoneNumber' ),
                new Rules\NullValue,
            ]));
        };

        //---

        parent::__construct( $data );

    } // function

} // class
