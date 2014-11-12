<?php
namespace Opg\Lpa\DataModel\Lpa\Payment;

use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;
use Opg\Lpa\DataModel\Lpa\Elements;

class Payment extends AbstractData {

    const PAYMENT_TYPE_CARD = 'card';
    const PAYMENT_TYPE_CHEQUE = 'cheque';

    protected $method;
    protected $phone;
    protected $amount;
    protected $reference;

    public function __construct(){
        parent::__construct();

        $this->method = self::PAYMENT_TYPE_CARD;
        $this->phone = new Elements\PhoneNumber();
        $this->amount = (float)100;
        $this->reference = 'abc123';

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)


    } // function

} // class
