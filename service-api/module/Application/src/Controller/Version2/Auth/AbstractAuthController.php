<?php

namespace Application\Controller\Version2\Auth;

use Application\Library\ApiProblem\ApiProblemException;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\RequestInterface as Request;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

abstract class AbstractAuthController extends AbstractRestfulController
{
    /**
     * @var string
     */
    protected $identifierName = 'userId';

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
     * Execute the request
     *
     * @param MvcEvent $event
     * @return mixed|ApiProblem|ApiProblemResponse
     * @throws ApiProblemException
     */
    public function onDispatch(MvcEvent $event)
    {
        $return = parent::onDispatch($event);

        if ($return instanceof ApiProblem) {
            return new ApiProblemResponse($return);
        }

        return $return;
    }

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

    /**
     * Get data from the body content of the request
     *
     * @param $varName
     * @return array|null|string
     */
    protected function getBodyContent($varName = null)
    {
        $data = $this->processBodyContent($this->getRequest());

        if (is_string($varName)) {
            //  Try to get the specific variable from the data
            return ($data[$varName] ? $data[$varName] : null);
        }

        return $data;
    }
}
