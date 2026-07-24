<?php

namespace Application\Controller\Version2\Auth;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\OneLogin\OneLoginAuthenticationException;
use Application\Model\Service\OneLogin\Service as OneLoginService;
use Laminas\View\Model\JsonModel;
use MakeShared\Logging\LoggerTrait;
use MakeShared\Telemetry\TelemetryEventManager;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class OneLoginController extends AbstractAuthController
{
    use LoggerTrait;

    protected function getService(): OneLoginService
    {
        /** @var OneLoginService $service */
        return $this->service;
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

    /**
     * @return JsonModel|ApiProblem
     */
    public function callbackAction(): JsonModel|ApiProblem
    {
        /** @var array<string, mixed>|null $body */
        $body = json_decode((string) $this->getRequest()->getContent(), true);

        foreach (['code', 'state', 'nonce', 'redirect_uri'] as $field) {
            if (empty($body[$field]) || !is_string($body[$field])) {
                return new ApiProblem(400, sprintf('%s must be provided', $field));
            }
        }

        TelemetryEventManager::triggerStart('OneLoginController.callbackAction');

        try {
            $result = $this->getService()->handleCallback(
                $body['code'],
                $body['state'],
                $body['nonce'],
                $body['redirect_uri'],
            );
        } catch (OneLoginAuthenticationException $e) {
            $this->getLogger()->error('auth.onelogin.callback_failed', ['reason' => $e->reason()]);
            return new ApiProblem(401, 'One Login authentication failed');
        } finally {
            TelemetryEventManager::triggerStop();
        }

        return new JsonModel($result);
    }
}
