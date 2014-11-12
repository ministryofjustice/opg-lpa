<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Elements;

use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class EmailAddress extends AbstractData {

    protected $email;

    public function __construct(){
        parent::__construct();

        # TEMPORARY TEST DATA ------------

        $this->email = 'test@digital.justice.gov.uk';

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['email'] = function(){
            return (new Validator)->addRules([
                new Rules\Email,
            ]);
        };

    } // function

} // class
