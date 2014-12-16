<?php
namespace Application\Model\Rest\Instruction;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface {

    protected $lpa;
    protected $instruction;

    public function __construct( $instruction, Lpa $lpa ){

        $this->lpa = $lpa;
        $this->instruction = $instruction;

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

        if( is_string($this->instruction) ){
            return [ 'instruction' => $this->instruction ];
        } else {
            return array();
        }

    }

} // class

