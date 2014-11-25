<?php
namespace Application\Controller\Version1;

use RuntimeException;

use Zend\Mvc\Controller\AbstractRestfulController;

use Application\Model\Resources\ResourceInterface;

use Zend\Mvc\MvcEvent;

use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

//---------------

use Hateoas\HateoasBuilder;
use Hateoas\Configuration\Annotation as Hateoas;

use Doctrine\Common\Annotations\AnnotationRegistry;

class RestController extends AbstractRestfulController {

    private $resource;

    public function __construct(){


    } // function

    //----------------------------------------------------

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

        if ($return instanceof ApiProblem) {
            return new ApiProblemResponse($return);
        }

        //$e->setResult($return);
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
        return new ApiProblem(405, 'The DELETE method has not been defined');
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


        $hateoas = HateoasBuilder::create()->build();

        $test = new \Application\Model\Resources\Application();

        $json = $hateoas->serialize($test, 'json');

        var_dump( $json ); exit();

        die('here');

        //--------------------

        if( !is_callable( [ $this->getResource(), 'fetchAll' ] ) ){
            return new ApiProblem(405, 'The GET method has not been defined');
        }

        $response = $this->getResource()->fetchAll();

        # TODO - Check the response.

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
        return new ApiProblem(405, 'The PUT method has not been defined');
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
        return new ApiProblem(405, 'The PATCH method has not been defined');
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
