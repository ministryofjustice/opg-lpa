<?php
namespace Application\Controller\Version1;

use RuntimeException;

use Zend\Mvc\Exception;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Controller\AbstractRestfulController;

use Application\Model\Rest\ResourceInterface;
use Application\Model\Rest\EntityInterface;
use Application\Model\Rest\RouteProviderInterface;

use Application\Library\Http\Response\NoContent as NoContentResponse;

use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

use Application\Library\Hal\Hal;
use Application\Library\Hal\HalResponse;
use Application\Library\Hal\Entity as HalEntity;

use ZfcRbac\Exception\UnauthorizedException;

class RestController extends AbstractRestfulController {

    private $resource;

    //---

    public function setResource( ResourceInterface $resource ){
        $this->resource = $resource;
        $this->identifierName = $resource->getIdentifier();
    } // function

    /**
     * @return ResourceInterface The Resource current being used.
     */
    public function getResource(){

        if( !isset($this->resource) || !($this->resource instanceof ResourceInterface) ){
            throw new RuntimeException('A resource has not been set');
        }

        return $this->resource;

    } // function

    //----------------------------------------------------

    public function onDispatch(MvcEvent $event) {

        try {

            $return = parent::onDispatch($event);

        } catch( UnauthorizedException $e ){
            # TODO
        }

        //---

        if ($return instanceof Hal) {
            return new HalResponse($return, 'json');
        }

        if ($return instanceof ApiProblem) {
            return new ApiProblemResponse($return);
        }

        return $return;

    } // function

    /**
     * Retrieve the identifier, if any
     *
     * Attempts to see if an identifier was passed in either the URI or the
     * query string, returning it if found. Otherwise, returns a boolean false.
     *
     * This override ensures a value of TRUE id always
     * returned if the resource is a singular.
     *
     * @param  \Zend\Mvc\Router\RouteMatch $routeMatch
     * @param  \Zend\Stdlib\RequestInterface $request
     * @return false|mixed
     */
    protected function getIdentifier($routeMatch, $request){

        $resource = $this->getResource();

        // If the resource is a singular,
        if( $resource->getType() == $resource::TYPE_SINGULAR ){
            return true;
        }

        return parent::getIdentifier( $routeMatch, $request );

    } // function

    //----------------------------------------------------

    /**
     * Create a new resource
     *
     * @param  mixed $data
     * @return mixed
     */
    public function create($data){

        if( !is_callable( [ $this->getResource(), 'create' ] ) ){
            return new ApiProblem(405, 'The POST method has not been defined on this entity');
        }

        $result = $this->getResource()->create( $data );

        //---

        if( $result instanceof ApiProblem ){

            return $result;

        } elseif( $result instanceof EntityInterface ) {

            $hal = $result->getHal( [ $this, 'generateRoute' ] );

            $response = new HalResponse( $hal, 'json' );
            $response->setStatusCode(201);
            $response->getHeaders()->addHeaderLine('Location', $hal->getUri() );

            return $response;

        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');

    }

    /**
     * Delete an existing resource
     *
     * @param  mixed $id
     * @return mixed
     */
    public function delete($id){

        if( !is_callable( [ $this->getResource(), 'delete' ] ) ){
            return new ApiProblem(405, 'The DELETE method has not been defined');
        }

        $result = @$this->getResource()->delete( $id );

        //---

        if( $result instanceof ApiProblem ){

            return $result;

        } elseif( $result === true ) {

            return new NoContentResponse();

        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');

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
    public function get($id){

        if( !is_callable( [ $this->getResource(), 'fetch' ] ) ){
            return new ApiProblem(405, 'The GET method has not been defined');
        }

        $result = $this->getResource()->fetch( $id );

        //---

        if( $result instanceof ApiProblem ){

            return $result;

        } elseif( $result instanceof EntityInterface ) {

            if( count($result->toArray()) == 0 ){
                return new NoContentResponse();
            }

            $hal = new HalEntity( $result );

            $hal->setLinks( [ $this, 'generateRoute' ] );

            $response = new HalResponse( $hal, 'json' );

            return $response;

        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');

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

        //---

        $query = $this->params()->fromQuery();

        if( isset($query['page']) && is_numeric($query['page']) ){
            $page = (int)$query['page'];
        } else {
            $page = 1;
        }

        unset($query['page']);

        //---

        $response = $this->getResource()->fetchAll( $query );

        $response->setCurrentPageNumber($page);

        //---

        $hal = $response->getHalItemsByPage( 1, [ $this, 'generateRoute' ] );

        //-------------------------------
        // Setup links...

        // 'first'...
        $hal->addLink( 'first', $this->generateRoute( $response, $query ) );

        // 'self'...
        if( $response->getCurrentPageNumber() == 1 ){
            $hal->addLink( 'self', $this->generateRoute( $response, $query ) );
        } else {
            $hal->addLink( 'self', $this->generateRoute( $response, [ 'page'=>$response->getCurrentPageNumber() ] + $query ) );
        }

        // 'prev'...
        if ($response->getCurrentPageNumber() - 1 > 0) {

            if ($response->getCurrentPageNumber() - 1 == 1) {
                $hal->addLink( 'prev', $this->generateRoute( $response, $query ) );
            } else {
                $hal->addLink( 'prev', $this->generateRoute( $response, [ 'page'=>($response->getCurrentPageNumber() - 1) ] + $query ) );
            }

        }

        // 'next'...
        if ($response->getCurrentPageNumber() + 1 <= $response->count()) {
            $hal->addLink( 'next', $this->generateRoute( $response, [ 'page'=>($response->getCurrentPageNumber() + 1) ] + $query ) );
        }

        // 'last'...
        if( $response->count() <= 1 ){
            $hal->addLink( 'last', $this->generateRoute( $response, $query ) );
        } else {
            $hal->addLink( 'last', $this->generateRoute( $response, [ 'page'=>$response->count() ] + $query ) );
        }

        //-------------------------------

        $response = new HalResponse( $hal, 'json' );

        return $response;

    } // function

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
    public function update($id, $data){

        if( !is_callable( [ $this->getResource(), 'update' ] ) ){
            return new ApiProblem(405, 'The PUT method has not been defined');
        }

        $result = @$this->getResource()->update( $data, $id );

        //---

        if( $result instanceof ApiProblem ){

            return $result;

        } elseif( $result instanceof EntityInterface ) {

            if( count($result->toArray()) == 0 ){
                return new NoContentResponse();
            }

            $hal = new HalEntity( $result );

            $hal->setLinks( [ $this, 'generateRoute' ] );

            $response = new HalResponse( $hal, 'json' );

            return $response;

        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');

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

    //-----------------------------------------

    public function generateRoute( $routeName, RouteProviderInterface $provider, $params = array() ){

        $resource = $this->getResource();

        /*
        if( $provider instanceof \Application\Model\Rest\Users\Entity ) {
            $routeName = 'api-v1';
        } elseif( $provider instanceof \Application\Model\Rest\Applications\Entity ){
            $routeName = 'api-v1/level-1';
        } elseif( $provider instanceof \Application\Model\Rest\Applications\Collection ){
            $routeName = 'api-v1/level-1';
        } else {
            $routeName = 'api-v1/level-2';
        }
        */

        return $this->url()->fromRoute($routeName, [
                'userId'=>$resource->getRouteUser()->userId(),
                'lpaId'=>$provider->lpaId(),
                'resource' => $resource->getName(),
                'resourceId' => $provider->resourceId()
            ],[ 'query' => $params ]);

    } // function

} // class
