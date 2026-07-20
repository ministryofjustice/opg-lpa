<?php

namespace Application\Controller\Version2\Auth;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\OneLogin\Service as OneLoginService;
use Laminas\View\Model\JsonModel;
use MakeShared\Telemetry\TelemetryEventManager;

class OneLoginController extends AbstractAuthController
{
    protected function getService(): OneLoginService
    {
        /** @var OneLoginService $service */
        $service = $this->service;

        return $service;
    }

    /**
     * @return JsonModel|ApiProblem
     */
    public function startAction(): JsonModel|ApiProblem
    {
        /** @psalm-suppress UndefinedInterfaceMethod */
        $redirectUrl = $this->params()->fromQuery('redirect_url');

        if (empty($redirectUrl)) {
            return new ApiProblem(400, 'redirect_url must be provided');
        }

        TelemetryEventManager::triggerStart('OneLoginController.startAction');

        $result = $this->getService()->createAuthenticationRequest($redirectUrl);

        TelemetryEventManager::triggerStop();

        return new JsonModel($result);
    }
}
