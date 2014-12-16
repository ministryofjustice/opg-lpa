<?php
namespace Application\Model\Rest\CertificateProvider;

use Application\Model\Rest\EntityInterface;

use Application\Model\Rest\Users\Entity as UsersEntity;
use Application\Model\Rest\Applications\Entity as ApplicationEntity;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\AccessorInterface as LpaAccessorInterface;

use Application\Library\Hal\Hal;

class Entity implements EntityInterface {

    protected $lpa;
    protected $provider;

    public function __construct( CertificateProvider $provider = null, Lpa $lpa ){

        $this->lpa = $lpa;
        $this->provider = $provider;

    }

    public function userId(){
        return $this->lpa->user;
    }

    public function lpaId(){
        return $this->lpa->id;
    }

    public function resourceId(){
        return null;
    }

    public function toArray(){

        if( $this->provider instanceof LpaAccessorInterface ){
            return $this->provider->toArray();
        } else {
            return array();
        }

    }

} // class
