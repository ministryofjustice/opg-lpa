<?php
namespace Opg\Lpa\DataModel\User;

use Opg\Lpa\DataModel\AbstractData;
use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

/**
 * Represents a user of the LPA platform.
 *
 * Class User
 */
class User extends AbstractData {

    /**
     * @var string The user's internal ID.
     */
    protected $id;

    /**
     * @var Name Their name.
     */
    protected $name;

    /**
     * @var Address Their postal address.
     */
    protected $address;

    /**
     * @var Dob Their date of birth.
     */
    protected $dob;

    /**
     * @var EmailAddress Their email address.
     */
    protected $email;

    //------------------------------------------------

    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Type mappers

        $this->typeMap['name'] = function($v){
            return ($v instanceof Name || is_null($v)) ? $v : new Name( $v );
        };

        $this->typeMap['address'] = function($v){
            return ($v instanceof Address || is_null($v)) ? $v : new Address( $v );
        };

        $this->typeMap['dob'] = function($v){
            return ($v instanceof Dob || is_null($v)) ? $v : new Dob( $v );
        };

        $this->typeMap['email'] = function($v){
            return ($v instanceof EmailAddress || is_null($v)) ? $v : new EmailAddress( $v );
        };

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['id'] = function(){
            return (new Validator)->addRules([
                new Rules\NotEmpty,
                new Rules\Xdigit,
                new Rules\Length( 32, 32, true ),
            ]);
        };

        $this->validators['name'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\User\Name' ),
            ]);
        };

        $this->validators['address'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\User\Address' ),
            ]);
        };

        $this->validators['dob'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\User\Dob' ),
            ]);
        };

        $this->validators['email'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\User\EmailAddress' ),
                new Rules\NullValue,
            ]));
        };

        //---

        parent::__construct( $data );

    } // function

} // class
