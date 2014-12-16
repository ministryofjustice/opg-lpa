<?php
namespace Application\Model\Rest\Applications;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Application\Library\Hal\Hal;

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

    public function getLpa(){
        return $this->lpa;
    }

    public function toArray(){
        return [ 'id' => $this->lpaId() ] +  $this->lpa->document->toArray();
    }

} // class
