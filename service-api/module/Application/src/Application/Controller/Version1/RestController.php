<?php
namespace Application\Controller\Version1;

use RuntimeException;

use Zend\Mvc\MvcEvent;
use Zend\Mvc\Controller\AbstractRestfulController;

use Application\Model\Resources\ResourceInterface;

use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

use Application\Library\Hal\Hal;
use Application\Library\Hal\HalResponse;

use Application\Library\Authentication\Identity\AbstractIdentity as Identity;


class RestController extends AbstractRestfulController {

    private $identity;
    private $resource;

    public function __construct(){


    } // function

    //----------------------------------------------------

    public function setIdentity( Identity $identity ){
        $this->identity = $identity;
    } // function

    public function getIdentity(){

        if( !isset($this->identity) || !($this->identity instanceof Identity) ){
            throw new RuntimeException('An identity has not been set');
        }

        return $this->identity;

    } // function

    //---

    public function setResource( ResourceInterface $resource ){
        $this->resource = $resource;
    } // function

    public function getResource(){

        if( !isset($this->resource) || !($this->resource instanceof ResourceInterface) ){
            throw new RuntimeException('A resource has not been set');
        }

        return $this->resource;

    } // function

    //----------------------------------------------------

    public function onDispatch(MvcEvent $e) {
        $return = parent::onDispatch($e);

        if ($return instanceof Hal) {
            return new HalResponse($return, 'json');
        }

        if ($return instanceof ApiProblem) {
            return new ApiProblemResponse($return);
        }

        return $return;

    } // function

    //----------------------------------------------------

    /**
     * Create a new resource
     *
     * @param  mixed $data
     * @return mixed
     */
    public function create($data)
    {
        return new ApiProblem(405, 'The POST method has not been defined');
    }

    /**
     * Delete an existing resource
     *
     * @param  mixed $id
     * @return mixed
     */
    public function delete($id)
    {
        return new ApiProblem(405, 'The DELETE method has not been defined');
    }

    /**
     * Delete the entire resource collection
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.1.0); instead, raises an exception if not implemented.
     *
     * @return mixed
     */
    public function deleteList()
    {
        return new ApiProblem(405, 'The DELETE method has not been defined on this collection');
    }

    /**
     * Return single resource
     *
     * @param  mixed $id
     * @return mixed
     */
    public function get($id)
    {
        return new ApiProblem(405, 'The GET method has not been defined');
    }

    /**
     * Return list of resources
     *
     * @return mixed
     */
    public function getList(){

        if( !is_callable( [ $this->getResource(), 'fetchAll' ] ) ){
            return new ApiProblem(405, 'The GET method has not been defined on this collection');
        }

        $response = $this->getResource()->fetchAll();

        # TODO - Check the response.

        # TODO - Map to Hal.

        return $response;

    }

    /**
     * Retrieve HEAD metadata for the resource
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.1.0); instead, raises an exception if not implemented.
     *
     * @param  null|mixed $id
     * @return mixed
     */
    public function head($id = null)
    {
        return new ApiProblem(405, 'The HEAD method has not been defined');
    }

    /**
     * Respond to the OPTIONS method
     *
     * Typically, set the Allow header with allowed HTTP methods, and
     * return the response.
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.1.0); instead, raises an exception if not implemented.
     *
     * @return mixed
     */
    public function options()
    {
        return new ApiProblem(405, 'The OPTIONS method has not been defined');
    }

    /**
     * Respond to the PATCH method
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.1.0); instead, raises an exception if not implemented.
     *
     * @param  $id
     * @param  $data
     */
    public function patch($id, $data)
    {
        return new ApiProblem(405, 'The PATCH method has not been defined');
    }

    /**
     * Replace an entire resource collection
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.1.0); instead, raises an exception if not implemented.
     *
     * @param  mixed $data
     * @return mixed
     */
    public function replaceList($data)
    {
        return new ApiProblem(405, 'The PUT method has not been defined on this collection');
    }

    /**
     * Modify a resource collection without completely replacing it
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.2.0); instead, raises an exception if not implemented.
     *
     * @param  mixed $data
     * @return mixed
     */
    public function patchList($data)
    {
        return new ApiProblem(405, 'The PATCH method has not been defined on this collection');
    }

    /**
     * Update an existing resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return mixed
     */
    public function update($id, $data)
    {
        return new ApiProblem(405, 'The PUT method has not been defined');
    }

    /**
     * Basic functionality for when a page is not available
     *
     * @return array
     */
    public function notFoundAction()
    {
        return new ApiProblem(404, 'Page not found');
    }

} // class
