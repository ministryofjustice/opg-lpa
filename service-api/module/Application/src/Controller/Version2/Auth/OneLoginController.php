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
 * @psalm-suppress DeprecatedClass
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
        /** @var mixed $body */
        $body = json_decode((string) $this->getRequest()->getContent(), true);

        if (!is_array($body)) {
            return new ApiProblem(400, 'A JSON request body must be provided');
        }

        $code        = $body['code'] ?? null;
        $state       = $body['state'] ?? null;
        $nonce       = $body['nonce'] ?? null;
        $redirectUri = $body['redirect_uri'] ?? null;

        if (!is_string($code) || $code === '') {
            return new ApiProblem(400, 'code must be provided');
        }
        if (!is_string($state) || $state === '') {
            return new ApiProblem(400, 'state must be provided');
        }
        if (!is_string($nonce) || $nonce === '') {
            return new ApiProblem(400, 'nonce must be provided');
        }
        if (!is_string($redirectUri) || $redirectUri === '') {
            return new ApiProblem(400, 'redirect_uri must be provided');
        }

        TelemetryEventManager::triggerStart('OneLoginController.callbackAction');

        try {
            $result = $this->getService()->handleCallback(
                $code,
                $state,
                $nonce,
                $redirectUri,
            );
        } catch (OneLoginAuthenticationException $e) {
            $this->getLogger()->error('auth.onelogin.callback_failed', ['reason' => $e->reason()]);
            return new ApiProblem(401, 'One Login authentication failed');
        } finally {
            TelemetryEventManager::triggerStop();
        }

        return new JsonModel($result);
    }

    /**
     * Link an existing Make account to a One Login identity that is not yet associated with any Make account.
     *
     * @return JsonModel|ApiProblem
     */
    public function linkAction(): JsonModel|ApiProblem
    {
        /** @var mixed $body */
        $body = json_decode((string) $this->getRequest()->getContent(), true);

        if (!is_array($body)) {
            return new ApiProblem(400, 'A JSON request body must be provided');
        }

        $username    = $body['username'] ?? null;
        $password    = $body['password'] ?? null;
        $oneLoginSub = $body['oneLoginSub'] ?? null;

        if (!is_string($username) || $username === '') {
            return new ApiProblem(400, 'username must be provided');
        }
        if (!is_string($password) || $password === '') {
            return new ApiProblem(400, 'password must be provided');
        }
        if (!is_string($oneLoginSub) || $oneLoginSub === '') {
            return new ApiProblem(400, 'oneLoginSub must be provided');
        }

        TelemetryEventManager::triggerStart('OneLoginController.linkAction');

        try {
            $result = $this->getService()->linkExistingAccount($username, $password, $oneLoginSub);
        } finally {
            TelemetryEventManager::triggerStop();
        }

        return new JsonModel($result);
    }
}
