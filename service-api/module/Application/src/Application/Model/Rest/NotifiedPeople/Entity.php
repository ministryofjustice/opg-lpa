<?php
namespace Application\Model\Rest\NotifiedPeople;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\AccessorInterface as LpaAccessorInterface;

class Entity implements EntityInterface {

    protected $lap;
    protected $person;

    public function __construct( NotifiedPerson $person = null, Lpa $lpa ){

        $this->lap = $lpa;
        $this->person = $person;

    }

    public function userId(){
        return $this->lap->userId;
    }

    public function lpaId(){
        return $this->lap->id;
    }

    public function resourceId(){
        return $this->person->id;
    }

    public function toArray(){
        if( $this->person instanceof LpaAccessorInterface ){
            return $this->person->toArray();
        } else {
            return array();
        }
    }

} // class
