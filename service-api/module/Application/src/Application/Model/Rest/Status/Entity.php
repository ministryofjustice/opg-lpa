<?php
namespace Application\Model\Rest\Status;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface {

    protected $lpa;

    public function __construct( Lpa $lpa ){
        $this->lpa = $lpa;
    } // function

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
        return $this->lpa->toArray();
    }

} // class
