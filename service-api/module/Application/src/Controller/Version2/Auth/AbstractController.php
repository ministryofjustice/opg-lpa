<?php

namespace Application\Controller\Version2\Auth;

use Auth\Model\Service\AbstractService;
use Auth\Model\Service\AuthenticationService;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Stdlib\RequestInterface as Request;

abstract class AbstractController extends AbstractActionController
{
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
    public function __construct(AuthenticationService $authenticationService, ?AbstractService $service)
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
     * @param Request $request
     * @param $userId
     * @param bool $extendToken
     * @return bool
     */
    protected function authenticateUserToken(Request $request, $userId, bool $extendToken = false)
    {
        if (($request instanceof HttpRequest) === false) {
            return false;
        }

        /** @var HttpRequest $request */
        $token = $request->getHeader('Token');

        if ($token === false) {
            // Header was not passed.
            return false;
        }

        $result = $this->authenticationService->withToken($token->getFieldValue(), $extendToken);

        if (!is_array($result) || !isset($result['userId']) || $result['userId'] !== $userId) {
            //Token does not match userId
            return false;
        }

        return true;
    }
}
