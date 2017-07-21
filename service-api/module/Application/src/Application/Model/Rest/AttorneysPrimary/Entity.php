<?php
namespace Application\Model\Rest\AttorneysPrimary;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\AccessorInterface as LpaAccessorInterface;

class Entity implements EntityInterface {

    protected $lpa;
    protected $attorney;

    public function __construct( AbstractAttorney $attorney = null, Lpa $lpa ){

        $this->lpa = $lpa;
        $this->attorney = $attorney;

    }

    public function userId(){
        return $this->lpa->user;
    }

    public function lpaId(){
        return $this->lpa->id;
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
