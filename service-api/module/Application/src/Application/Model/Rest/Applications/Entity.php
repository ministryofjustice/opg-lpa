<?php
namespace Application\Model\Rest\Applications;

use Application\Model\Rest\EntityInterface;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Application\Library\Hal\Hal;

class Entity implements EntityInterface {

    protected $lap;

    public function __construct( Lpa $lpa ){
        $this->lap = $lpa;
    } // function

    public function lpaId(){
        return $this->lap->id;
    }

    public function resourceId(){
        return null;
    }

    public function getLpa(){
        return $this->lap;
    }

    public function getHal( callable $routeCallback ){

        $hal = new Hal( call_user_func($routeCallback, $this) );

        // Add the id to the document...
        $data = [ 'id' => $this->lpaId() ] +  $this->lap->document->toArray();

        //The data comes from the Document (not the root of the object)...
        $hal->setData( $data );

        return $hal;

    }

} // class
