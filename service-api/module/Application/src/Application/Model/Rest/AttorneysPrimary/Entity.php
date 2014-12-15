<?php
namespace Application\Model\Rest\AttorneysPrimary;

use Application\Model\Rest\EntityInterface;

use Application\Model\Rest\Users\Entity as UsersEntity;
use Application\Model\Rest\Applications\Entity as ApplicationEntity;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;

use Application\Library\Hal\Hal;

class Entity implements EntityInterface {

    protected $lap;
    protected $attorney;

    public function __construct( AbstractAttorney $attorney = null, Lpa $lpa ){

        $this->lap = $lpa;
        $this->attorney = $attorney;

    }

    public function lpaId(){
        return $this->lap->id;
    }

    public function resourceId(){
        return $this->attorney->id;
    }

    public function getHal( callable $routeCallback ){

        $hal = new Hal( call_user_func($routeCallback, $this) );

        if( $this->attorney instanceof AbstractAttorney ){
            $hal->setData( [ 'attorney' => $this->attorney->toArray() ] );
        } else {
            $hal->setData( [ 'attorney' => null ] );
        }

        # TODO - do I want to include these for all entities?
        $hal->addLink( 'user', call_user_func($routeCallback, new UsersEntity( ['id'=>$this->lap->user] ) ) );
        $hal->addLink( 'application', call_user_func($routeCallback, new ApplicationEntity($this->lap) ) );

        return $hal;

    } // function

} // class
