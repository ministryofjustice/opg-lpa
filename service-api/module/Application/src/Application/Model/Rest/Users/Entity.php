<?php
namespace Application\Model\Rest\Users;

use InvalidArgumentException;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\User\User;

class Entity implements EntityInterface {

    protected $user;

    public function __construct( User $user ){

        $this->user = $user;

    } // function

    public function userId(){
        return $this->user->id;
    }

    public function lpaId(){
        return null;
    }

    public function resourceId(){
        return null;
    }

    public function toArray(){
        return $this->user->toArray();
    }

} // class

