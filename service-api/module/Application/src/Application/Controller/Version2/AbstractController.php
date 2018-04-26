<?php

namespace Application\Controller\Version2;

use Application\Model\Service\AbstractService;
use Application\Model\Service\Lock\LockedException;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use ZfcRbac\Exception\UnauthorizedException;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

abstract class AbstractController extends AbstractRestfulController
{
    /**
     * Name of the identifier used in the routes to this RESTful controller - by default this is the LPA ID
     *
     * @var string
     */
    protected $identifierName = 'lpaId';

    /**
     * @var AbstractService
     */
    protected $service;

    /**
     * @param AbstractService $service
     */
    public function __construct(AbstractService $service)
    {
        $this->service = $service;
    }

    /**
     * Execute the request
     *
     * @param MvcEvent $event
     * @return mixed|ApiProblem|ApiProblemResponse
     */
    public function onDispatch(MvcEvent $event)
    {
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
}
