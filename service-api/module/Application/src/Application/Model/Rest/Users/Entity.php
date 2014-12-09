<?php
namespace Application\Model\Rest\Users;

use InvalidArgumentException;

use Application\Model\Rest\EntityInterface;

use Application\Library\Hal\Hal;

class Entity implements EntityInterface {

    protected $user;

    public function __construct( Array $user ){

        if( !isset($user['id']) ){
            throw new InvalidArgumentException('No user ID passed.');
        }

        $this->user = $user;

    } // function

    public function userId(){
        return $this->user['id'];
    }

    public function lpaId(){
        return null;
    }

    public function resourceId(){
        return null;
    }

    public function getHal( callable $routeCallback ){

        $hal = new Hal( call_user_func($routeCallback, $this) );

        //The data comes from the Document (not the root of the object)...
        $hal->setData( $this->user );

        return $hal;

    } // function

} // class

