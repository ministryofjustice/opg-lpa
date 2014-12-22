<?php
namespace Application\Model\Rest\Correspondent;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\AccessorInterface as LpaAccessorInterface;

class Entity implements EntityInterface {

    protected $lpa;
    protected $correspondence;

    public function __construct( Correspondence $correspondence = null, Lpa $lpa ){

        $this->lpa = $lpa;
        $this->correspondence = $correspondence;

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

        if( $this->correspondence instanceof LpaAccessorInterface ){
            return $this->correspondence->toArray();
        } else {
            return array();
        }

    }

} // class
