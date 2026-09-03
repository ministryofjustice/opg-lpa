<?php

namespace Application\Controller\Version2\Lpa;

use Application\Library\ApiProblem\ApiProblemException;
use Application\Library\Authentication\Identity\Guest;
use Application\Library\Authorization\UnauthorizedException;
use Application\Model\Service\AbstractService;
use Laminas\Authentication\AuthenticationService;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Psr\Log\LoggerInterface;

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

    private bool $enforceConflictEnabled;

    /**
     * @param AuthenticationService $authenticationService
     * @param AbstractService $service
     */
    public function __construct(
        AuthenticationService $authenticationService,
        AbstractService $service,
        private readonly ?LoggerInterface $abstractLogger = null,
    ) {
        $this->authenticationService = $authenticationService;
        $this->service = $service;
        $this->enforceConflictEnabled = getenv('ENFORCE_CONFLICT_ENABLED') === 'true';
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

    protected function ifMatch(): ?int
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $header = $request->getHeader('If-Match');

        if (!$header) {
            if ($this->enforceConflictEnabled) {
                throw new ApiProblemException('If-Match header required', 400);
            } else {
                $this->abstractLogger?->warning('If-Match header required', [
                    'uri' => $request->getUriString(),
                    'method' => $request->getMethod(),
                ]);

                return null;
            }
        }

        $version = (int) $header->getFieldValue();
        if ($version === 0) {
            if ($this->enforceConflictEnabled) {
                throw new ApiProblemException('If-Match header value required', 400);
            } else {
                $this->abstractLogger?->warning('If-Match header value required', [
                    'uri' => $request->getUriString(),
                    'method' => $request->getMethod(),
                ]);

                return null;
            }
        }

        return $version;
    }
}
