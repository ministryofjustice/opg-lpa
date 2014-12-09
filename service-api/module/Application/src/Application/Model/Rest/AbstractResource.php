<?php
namespace Application\Model\Rest;

use Application\Model\Rest\Users\Entity as RouteUser;
use Opg\Lpa\DataModel\Lpa\Lpa;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use ZfcRbac\Exception\UnauthorizedException;
use ZfcRbac\Service\AuthorizationServiceAwareTrait;

abstract class AbstractResource implements ResourceInterface, ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    /**
     * Identity and authorization for the authenticated user. This could be Identity\Guest.
     */
    use AuthorizationServiceAwareTrait;

    //------------------------------------------

    protected $lpa = null;

    protected $routeUser = null;

    //------------------------------------------

    public function setRouteUser( RouteUser $user ){
        $this->routeUser = $user;
    }

    /**
     * @return RouteUser
     */
    public function getRouteUser(){
       return $this->routeUser;
    }

    //--------------------------

    public function setLpa( Lpa $lpa ){
        $this->lpa = $lpa;
    }

    public function getLpa(){
        return $this->lpa;
    }

    //--------------------------

    /**
     * @param $collection string Name of the requested collection.
     * @return \MongoCollection
     */
    public function getCollection( $collection ){
        return $this->getServiceLocator()->get( "MongoDB-Default-{$collection}" );
    }

    //--------------------------

    public function checkAccess( $userId = null ){

        if( is_null($userId) && $this->getRouteUser() != null ){
            $userId = $this->getRouteUser()->userId();
        }

        if (!$this->getAuthorizationService()->isGranted('authenticated')) {
            throw new UnauthorizedException('You need to be authenticated to access this resource');
        }

        if ( !$this->getAuthorizationService()->isGranted('isAuthorizedToManageUser', $userId) ) {
            throw new UnauthorizedException('You do not have permission to access this resource');
        }

    } // function

    /**
     * Create a resource
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    #public function create($data){}

    /**
     * Delete a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    #public function delete($id){}

    /**
     * Delete a collection, or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    #public function deleteList($data){}

    /**
     * Fetch a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    #public function fetch($id){}

    /**
     * Fetch all or a subset of resources
     *
     * @param  array $params
     * @return ApiProblem|mixed
     */
    #public function fetchAll($params = array()){}

    /**
     * Patch (partial in-place update) a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    #public function patch($id, $data){}

    /**
     * Replace a collection or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    #public function replaceList($data){}

    /**
     * Update a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    #public function update($id, $data){}

} // abstract class
