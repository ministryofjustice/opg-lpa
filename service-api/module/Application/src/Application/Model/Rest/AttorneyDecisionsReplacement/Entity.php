<?php

namespace Application\Model\Rest\AttorneyDecisionsReplacement;

use Opg\Lpa\DataModel\AccessorInterface as LpaAccessorInterface;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Application\Model\Rest\EntityInterface;

class Entity implements EntityInterface {

    protected $lpa;
    protected $decisions;

    public function __construct( ReplacementAttorneyDecisions $decisions = null, Lpa $lpa ){

        $this->lpa = $lpa;
        $this->decisions = $decisions;

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

        if( $this->decisions instanceof LpaAccessorInterface ){
            return $this->decisions->toArray();
        } else {
            return array();
        }

    }

} // class
