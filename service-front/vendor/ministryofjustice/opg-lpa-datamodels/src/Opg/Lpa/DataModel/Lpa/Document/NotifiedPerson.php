<?php
namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

/**
 * Represents a person to be notified.
 *
 * Class NotifiedPerson
 * @package Opg\Lpa\DataModel\Lpa\Document
 */
class NotifiedPerson extends AbstractData {

    /**
     * @var int The person's internal ID.
     */
    protected $id;

    /**
     * @var Elements\Name Their name.
     */
    protected $name;

    /**
     * @var Elements\Address Their postal address.
     */
    protected $address;


    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Type mappers

        $this->typeMap['name'] = function($v){
            return ($v instanceof Elements\Name) ? $v : new Elements\Name( $v );
        };

        $this->typeMap['address'] = function($v){
            return ($v instanceof Elements\Address) ? $v : new Elements\Address( $v );
        };

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['id'] = function(){
            return (new Validator)->addRules([
                new Rules\Int,
            ]);
        };

        $this->validators['name'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\Name' ),
            ]);
        };

        $this->validators['address'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\Lpa\Elements\Address' ),
            ]);
        };

        //---

        parent::__construct( $data );

    } // function

} // class
