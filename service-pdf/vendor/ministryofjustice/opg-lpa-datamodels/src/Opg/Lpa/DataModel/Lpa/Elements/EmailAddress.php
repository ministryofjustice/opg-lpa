<?php
namespace Opg\Lpa\DataModel\Lpa\Elements;

use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

/**
 * Represents an email address.
 *
 * Class EmailAddress
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class EmailAddress extends AbstractData {

    /**
     * @var string An email address.
     */
    protected $address;

    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['address'] = function(){
            return (new Validator)->addRules([
                new Rules\Email,
            ]);
        };

        //---

        parent::__construct( $data );

    } // function

} // class
