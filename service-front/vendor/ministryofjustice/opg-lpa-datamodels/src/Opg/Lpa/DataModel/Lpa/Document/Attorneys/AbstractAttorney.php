<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Elements;
use Opg\Lpa\DataModel\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

/**
 * Base Represents of an Attorney. This can be extended with one of two types, either Human or TrustCorporation.
 *
 * Class AbstractAttorney
 * @package Opg\Lpa\DataModel\Lpa\Document\Attorneys
 */
abstract class AbstractAttorney extends AbstractData {

    /**
     * @var int The attorney's internal ID.
     */
    protected $id;

    /**
     * @var Elements\Address Their postal address.
     */
    protected $address;

    /**
     * @var Elements\EmailAddress Their email address.
     */
    protected $email;


    public function __construct( $data ){

        //-----------------------------------------------------
        // Type mappers

        $this->typeMap['address'] = function($v){
            return ($v instanceof Elements\Address) ? $v : new Elements\Address( $v );
        };

        $this->typeMap['email'] = function($v){
            return ($v instanceof Elements\EmailAddress) ? $v : new Elements\EmailAddress( $v );
        };

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['id'] = function(){
            return (new Validator)->addRules([
                new Rules\Int,
            ]);
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

        //---

        parent::__construct( $data );

    } // function

} // abstract class
