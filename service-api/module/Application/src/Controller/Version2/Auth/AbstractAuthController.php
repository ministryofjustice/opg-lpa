<?php

namespace Application\Controller\Version2\Auth;

use Application\Library\ApiProblem\ApiProblemException;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use MakeShared\Logging\LoggerTrait;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\RequestInterface as Request;
use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\ApiProblemResponse;

abstract class AbstractAuthController extends AbstractRestfulController
{
    use LoggerTrait;

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
     * @param MvcEvent $e
     * @return mixed|ApiProblem|ApiProblemResponse
     * @throws ApiProblemException
     */
    public function onDispatch(MvcEvent $e)
    {
        $return = parent::onDispatch($e);

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
     * @param null|string $varName
     *
     * @return mixed|null
     */
    protected function getBodyContent(string|null $varName = null)
    {
        $data = $this->processBodyContent($this->getRequest());

        if (is_string($varName)) {
            // Try to get the specific variable from the data
            return (array_key_exists($varName, $data) ? $data[$varName] : null);
        }

        return $data;
    }
}
