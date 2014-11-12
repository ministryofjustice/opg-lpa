<?php
namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\Lpa\AbstractData;
use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class Correspondence extends AbstractData {

    const WHO_DONOR = 'xxx';
    const WHO_ATTORNEY = 'xxx';
    const WHO_OTHER = 'xxx';

    protected $who;
    protected $name;
    protected $company;
    protected $address;
    protected $email;
    protected $phone;

    public function __construct(){
        parent::__construct();

        $this->who = 'other';
        $this->name = new Elements\Name();
        $this->company = 'My Company Limited';
        $this->address = new Elements\Address();
        $this->email = new Elements\EmailAddress();
        $this->phone = new Elements\PhoneNumber();

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)


    } // function

} // class
