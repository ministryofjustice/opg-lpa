<?php
namespace Application\Model\Rest\Type;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface {

    protected $lpa;
    protected $type;

    public function __construct( $type, Lpa $lpa ){

        $this->lpa = $lpa;
        $this->type = $type;

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

        if( is_string($this->type) ){
            return [ 'type' => $this->type ];
        } else {
            return array();
        }

    }

} // class

