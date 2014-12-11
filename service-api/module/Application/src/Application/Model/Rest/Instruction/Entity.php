<?php
namespace Application\Model\Rest\Instruction;

use Application\Model\Rest\EntityInterface;

use Application\Model\Rest\Users\Entity as UsersEntity;
use Application\Model\Rest\Applications\Entity as ApplicationEntity;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Application\Library\Hal\Hal;

class Entity implements EntityInterface {

    protected $lap;
    protected $instruction;

    public function __construct( $instruction, Lpa $lpa ){

        $this->lap = $lpa;
        $this->instruction = $instruction;

    }

    public function lpaId(){
        return $this->lap->id;
    }

    public function resourceId(){
        return null;
    }

    public function getHal( callable $routeCallback ){

        $hal = new Hal( call_user_func($routeCallback, $this) );

        $hal->setData( [ 'instruction' => $this->instruction ] );

        # TODO - do I want to include these for all entities?
        $hal->addLink( 'user', call_user_func($routeCallback, new UsersEntity( ['id'=>$this->lap->user] ) ) );
        $hal->addLink( 'application', call_user_func($routeCallback, new ApplicationEntity($this->lap) ) );

        return $hal;

    } // function

} // class

