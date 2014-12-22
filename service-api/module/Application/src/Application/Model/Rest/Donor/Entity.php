<?php
namespace Application\Model\Rest\Donor;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\AccessorInterface as LpaAccessorInterface;

class Entity implements EntityInterface {

    protected $lpa;
    protected $donor;

    public function __construct( Donor $donor = null, Lpa $lpa ){

        $this->lpa = $lpa;
        $this->donor = $donor;

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
        if( $this->donor instanceof LpaAccessorInterface ){
            return $this->donor->toArray();
        } else {
            return array();
        }
    }

} // class
