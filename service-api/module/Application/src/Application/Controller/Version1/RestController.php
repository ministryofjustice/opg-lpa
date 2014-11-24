<?php
namespace Application\Controller\Version1;

use Zend\Mvc\Controller\AbstractRestfulController;

use Application\Model\Resources\ResourceInterface;

class RestController extends AbstractRestfulController {

    protected $resource;

    public function __construct(){



    } // function

    public function setResource( ResourceInterface $resource ){
        $this->response = $resource;
    } // function

    public function getResource(){

        if( !isset($this->response) ){
            //Error!
        }

        return $this->response;

    } // function

} // class
