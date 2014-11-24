<?php
namespace Application\Controller\Version1;

use Zend\Mvc\Controller\AbstractRestfulController;

use Application\Model\Resources\ResourceInterface;

use Zend\Mvc\MvcEvent;

use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

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

    public function onDispatch(MvcEvent $e) {
        $return = parent::onDispatch($e);

        if ($return instanceof ApiProblem) {
            return new ApiProblemResponse($return);
        }

        $e->setResult($return);
        return $return;

    }

    public function get($id){

        //return new ApiProblem("You do not have enough credit.", "http://example.com/probs/out-of-credit");

        return new ApiProblem(405, 'The PUT method has not been defined for collections');


    }

} // class
