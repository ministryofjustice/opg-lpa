<?php
namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\Lpa\AbstractData;
use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class Correspondence extends AbstractData {

    const WHO_DONOR = 'donor';
    const WHO_ATTORNEY = 'attorney';
    const WHO_OTHER = 'other';

    protected $who;
    protected $name;
    protected $company;
    protected $address;
    protected $email;
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

        //---

        parent::__construct( $data );

    } // function

} // class
