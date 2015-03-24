<?php
namespace Application\Model\Rest\Status;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface {

    private $status;
    protected $lpa;

    public function __construct( Array $status, Lpa $lpa ){
        $this->lpa = $lpa;
        $this->status = $status;
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
        return $this->status;
    }

} // class
