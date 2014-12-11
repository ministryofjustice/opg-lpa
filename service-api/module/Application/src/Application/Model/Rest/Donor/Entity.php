<?php
namespace Application\Model\Rest\Donor;

use Application\Model\Rest\EntityInterface;

use Application\Model\Rest\Users\Entity as UsersEntity;
use Application\Model\Rest\Applications\Entity as ApplicationEntity;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Donor;

use Application\Library\Hal\Hal;

class Entity implements EntityInterface {

    protected $lap;
    protected $donor;

    public function __construct( Donor $donor = null, Lpa $lpa ){

        $this->lap = $lpa;
        $this->donor = $donor;

    }

    public function lpaId(){
        return $this->lap->id;
    }

    public function resourceId(){
        return null;
    }

    public function getHal( callable $routeCallback ){

        $hal = new Hal( call_user_func($routeCallback, $this) );

        if( $this->donor instanceof Donor ){
            $hal->setData( [ 'donor' => $this->donor->toArray() ] );
        } else {
            $hal->setData( [ 'donor' => null ] );
        }

        # TODO - do I want to include these for all entities?
        $hal->addLink( 'user', call_user_func($routeCallback, new UsersEntity( ['id'=>$this->lap->user] ) ) );
        $hal->addLink( 'application', call_user_func($routeCallback, new ApplicationEntity($this->lap) ) );

        return $hal;

    } // function

} // class
