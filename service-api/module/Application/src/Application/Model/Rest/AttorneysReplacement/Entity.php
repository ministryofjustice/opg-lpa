<?php
namespace Application\Model\Rest\AttorneysReplacement;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\AccessorInterface as LpaAccessorInterface;

class Entity implements EntityInterface {

    protected $lap;
    protected $attorney;

    public function __construct( AbstractAttorney $attorney = null, Lpa $lpa ){

        $this->lap = $lpa;
        $this->attorney = $attorney;

    }

    public function userId(){
        return $this->userId;
    }

    public function lpaId(){
        return $this->lap->id;
    }

    public function resourceId(){
        return $this->attorney->id;
    }

    public function toArray(){
        if( $this->attorney instanceof LpaAccessorInterface ){
            return $this->attorney->toArray();
        } else {
            return array();
        }
    }

} // class
