<?php

namespace Application\Controller\Version2;

use Application\Library\ApiProblem\ApiProblemException;
use Application\Library\Authorization\UnauthorizedException;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Lock\LockedException;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use ZfcRbac\Exception\UnauthorizedException as ZFCUnauthorizedException;
use ZfcRbac\Service\AuthorizationService;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

abstract class AbstractController extends AbstractRestfulController
{
    /**
     * Name of the identifier used in the routes to this RESTful controller
     * NOTE: This may be overridden by some child controllers
     *
     * @var string
     */
    protected $identifierName = 'lpaId';

    /**
     * @var AuthorizationService
     */
    protected $authorizationService;

    /**
     * @var mixed
     */
    protected $service;

    /**
     * @var string
     */
    protected $routeUserId;

    /**
     * @var string
     */
    protected $lpaId;

    /**
     * @param AuthorizationService $authorizationService
     * @param AbstractService $service
     */
    public function __construct(AuthorizationService $authorizationService, AbstractService $service)
    {
        $this->authorizationService = $authorizationService;
        $this->service = $service;
    }

    /**
     * Get the service to use
     * Abstract function here so that this can be implemented in the subclass controllers and type hint appropriately
     *
     * @return AbstractService
     */
    abstract protected function getService();

    /**
     * Execute the request
     *
     * @param MvcEvent $event
     * @return mixed|ApiProblem|ApiProblemResponse
     * @throws ApiProblemException
     */
    public function onDispatch(MvcEvent $event)
    {
        //  If possible get the user and LPA from the ID values in the route
        $this->routeUserId = $event->getRouteMatch()->getParam('userId');

        if (empty($this->routeUserId)) {
            //  userId MUST be present in the URL
            throw new ApiProblemException('User identifier missing from URL', 400);
        }

        //  The lpaId MAY be present in the URL
        $this->lpaId = $event->getRouteMatch()->getParam('lpaId');

        try {
            $return = parent::onDispatch($event);
        } catch (UnauthorizedException $e) {
            $return = new ApiProblem(401, 'Access Denied');
        } catch (LockedException $e) {
            $return = new ApiProblem(403, 'LPA has been locked');
        }

        if ($return instanceof ApiProblem) {
            return new ApiProblemResponse($return);
        }

        return $return;
    }

    /**
     * TODO - Move this code into the dispatch above? Need to make sure that the correct results are returned or thrown
     */
    protected function checkAccess()
    {
        if (!$this->authorizationService->isGranted('authenticated')) {
            throw new ZFCUnauthorizedException('You need to be authenticated to access this service');
        }

        if (!$this->authorizationService->isGranted('isAuthorizedToManageUser', $this->routeUserId) && !$this->authorizationService->isGranted('admin')) {
            throw new ZFCUnauthorizedException('You do not have permission to access this service');
        }
    }
}
