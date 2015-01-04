<?php
namespace Opg\Lpa\DataModel\User;

use DateTime;

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
     * @var DateTime the user was created.
     */
    protected $createdAt;

    /**
     * @var DateTime the user was last updated.
     */
    protected $updatedAt;

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

        $this->typeMap['updatedAt'] = $this->typeMap['createdAt'] = function($v){
            return ($v instanceof DateTime) ? $v : new DateTime( $v );
        };

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

        $this->validators['createdAt'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'DateTime' ),
                new Rules\Call(function($input){
                    return ( $input instanceof \DateTime ) ? $input->gettimezone()->getName() : 'UTC';
                }),
            ]);
        };

        $this->validators['updatedAt'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'DateTime' ),
                new Rules\Call(function($input){
                    return ( $input instanceof \DateTime ) ? $input->gettimezone()->getName() : 'UTC';
                }),
            ]);
        };

        $this->validators['name'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\User\Name' ),
            ]);
        };

        $this->validators['address'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\User\Address' ),
                new Rules\NullValue,
            ]));
        };

        $this->validators['dob'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                new Rules\Instance( 'Opg\Lpa\DataModel\User\Dob' ),
                new Rules\NullValue,
            ]));
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

    //--------------------------------------------------------------------

    /**
     * Returns $this as an array suitable for inserting into MongoDB.
     *
     * @return array
     */
    public function toMongoArray(){
        $data = parent::toMongoArray();

        // Rename 'id' to '_id' (keeping it at the beginning of the array)
        $data = [ '_id'=>$data['id'] ] + $data;

        unset($data['id']);

        return $data;
    }

} // class
