<?php

namespace Application\Controller\Version2\Lpa;

use Application\Library\Authentication\Identity\Guest;
use Application\Library\Authorization\UnauthorizedException;
use Application\Model\Service\AbstractService;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\Controller\AbstractRestfulController;

abstract class AbstractLpaController extends AbstractRestfulController
{
    /**
     * Name of the identifier used in the routes to this RESTful controller
     * NOTE: This may be overridden by some child controllers
     *
     * @var string
     */
    protected $identifierName = 'lpaId';

    /**
     * @var AuthenticationService
     */
    protected $authenticationService;

    /**
     * @var mixed
     */
    protected $service;

    /**
     * @param AuthenticationService $authenticationService
     * @param AbstractService $service
     */
    public function __construct(AuthenticationService $authenticationService, AbstractService $service)
    {
        $this->authenticationService = $authenticationService;
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
     * TODO - Move this code into the dispatch above? Need to make sure that the correct results are returned or thrown
     *
     * @return void
     */
    protected function checkAccess()
    {
        $identity = $this->authenticationService->getIdentity();

        if ($identity === null || $identity instanceof Guest) {
            throw new UnauthorizedException('You need to be authenticated to access this service');
        }

        if (
            $identity->getId() !== $this->params()->fromRoute('userId') &&
            !$identity->hasRole('admin') &&
            !$identity->hasRole('admin-service')
        ) {
            throw new UnauthorizedException('You do not have permission to access this service');
        }
    }
}
