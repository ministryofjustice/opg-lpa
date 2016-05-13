<?php
namespace Application\Controller\Version2;

use Zend\Mvc\MvcEvent;

use Zend\Mvc\Controller\AbstractRestfulController;

use Application\Model\Rest\ResourceInterface;
use Application\Model\Rest\EntityInterface;
use Application\Model\Rest\RouteProviderInterface;

use Application\Model\Rest\Lock\LockedException;

use Zend\Http\Response as HttpResponse;

use Application\Library\Http\Response\NoContent as NoContentResponse;

use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

use Application\Library\Hal\Hal;
use Application\Library\Hal\HalResponse;
use Application\Library\Hal\Entity as HalEntity;
use Application\Library\Hal\Collection as HalCollection;

use ZfcRbac\Exception\UnauthorizedException;

class ApplicationController extends AbstractRestfulController {

    protected $identifierName = 'lpaId';

    private function getResource(){
        return $this->getServiceLocator()->get('resource-applications');
    }

    //---

    public function onDispatch(MvcEvent $event) {

        try {

            $return = parent::onDispatch($event);

        } catch( UnauthorizedException $e ){
            $return = new ApiProblem(401, 'Access Denied');
        } catch ( LockedException $e ){
            $return = new ApiProblem( 403, 'LPA has been locked' );
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

    //--------------------------------------

    public function getList()
    {

        // @todo

        $this->response->setStatusCode(405);

        return [
            'content' => 'Method Not Allowed'
        ];

        //---

        $query = $this->params()->fromQuery();

        if( isset($query['page']) && is_numeric($query['page']) ){
            $page = (int)$query['page'];
        } else {
            $page = 1;
        }

        unset($query['page']);

        //---

        $collections = $this->getResource()->fetchAll( $query );

        //---

        if( $collections instanceof ApiProblem ){

            return $collections;

        } elseif( $collections === null ) {

            return new NoContentResponse();

        }

        $collections->setCurrentPageNumber($page);

        //---

        var_dump($collections->toArray()); die;

        $hal = new HalCollection( $collections, $this->getResource()->getName() );

        //$hal->setLinks( [ $this, 'generateRoute' ] );

        return new HalResponse( $hal, 'json' );

    }

    //--------------------------------------

    public function get($id){

        $result = $this->getResource()->fetch( $id );

        //---

        if( $result instanceof ApiProblem ){

            return $result;

        } elseif( $result instanceof EntityInterface ) {

            if( count($result->toArray()) == 0 ){
                return new NoContentResponse();
            }

            $hal = $this->generateHal( $result );

            return new HalResponse( $hal, 'json' );

        } elseif( $result instanceof HttpResponse ){

            return $result;

        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');

    }

    public function create($data){

        $result = $this->getResource()->create( $data );

        //---

        if( $result instanceof ApiProblem ){

            return $result;

        } elseif( $result instanceof EntityInterface ) {

            $hal = $this->generateHal( $result );

            $response = new HalResponse( $hal, 'json' );
            $response->setStatusCode(201);
            $response->getHeaders()->addHeaderLine('Location', $hal->getUri() );

            return $response;

        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');

    }

    public function delete($id) {

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

    public function patch($id, $data)
    {

        $result = $this->getResource()->patch( $data, $id );

        //---

        if( $result instanceof ApiProblem ){

            return $result;

        } elseif( $result instanceof EntityInterface ) {

            $hal = $this->generateHal( $result );

            $response = new HalResponse( $hal, 'json' );
            $response->setStatusCode(201);
            $response->getHeaders()->addHeaderLine('Location', $hal->getUri() );

            return $response;

        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');

    }

    //--------------------

    private function generateHal( EntityInterface $entity ){

        $hal = new Hal(
            // Set 'self'
            $this->url()->fromRoute('api-v2/user/applications', [
                'userId'=>$this->getResource()->getRouteUser()->userId(),
                'lpaId'=>$entity->lpaId(),
            ]),
            // Set the data
            $entity->toArray()
        );

        // Added 'user' link
        $hal->addLink( 'user', $this->url()->fromRoute('api-v2/user', [
            'userId'=>$this->getResource()->getRouteUser()->userId(),
        ]) );

        return $hal;

    }

}
