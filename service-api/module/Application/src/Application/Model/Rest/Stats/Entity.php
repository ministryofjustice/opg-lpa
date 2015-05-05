<?php
namespace Application\Model\Rest\Stats;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\Lpa\Lpa;

class Entity implements EntityInterface {

    private $stats;

    public function __construct( Array $stats ){

        $this->stats = $stats;

    } // function

    public function userId(){
        return null;
    }

    public function lpaId(){
        return null;
    }

    public function resourceId(){
        return null;
    }

    public function toArray(){
        return $this->stats;
    }

} // class
