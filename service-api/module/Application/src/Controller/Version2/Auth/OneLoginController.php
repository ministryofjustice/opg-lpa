<?php

namespace Application\Controller\Version2\Auth;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\OneLogin\OneLoginAuthenticationException;
use Application\Model\Service\OneLogin\Service as OneLoginService;
use InvalidArgumentException;
use Laminas\View\Model\JsonModel;
use MakeShared\Logging\LoggerTrait;
use MakeShared\OneLogin\UserType;
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
        /** @var array{username: string, password: string, oneLoginSub: string, oneLoginEmail: string, userType?: mixed} $body */
        $body = json_decode((string) $this->getRequest()->getContent(), true);

        $userType = $this->userType($body['userType'] ?? null);

        if ($userType === null) {
            return new ApiProblem(400, 'userType must be one of: lay, professional');
        }

        TelemetryEventManager::triggerStart('OneLoginController.linkAction');

        try {
            $result = $this->getService()->linkExistingAccount(
                $body['username'],
                $body['password'],
                $body['oneLoginSub'],
                $body['oneLoginEmail'],
                $userType,
            );
        } finally {
            TelemetryEventManager::triggerStop();
        }

        return new JsonModel($result);
    }

    /**
     * Build the URL that signs the user out of GOV.UK One Login.
     *
     * @return JsonModel|ApiProblem
     */
    public function logoutAction(): JsonModel|ApiProblem
    {
        /** @var mixed $body */
        $body = json_decode((string) $this->getRequest()->getContent(), true);

        if (!is_array($body)) {
            return new ApiProblem(400, 'A JSON request body must be provided');
        }

        $idToken               = $body['idToken'] ?? null;
        $postLogoutRedirectUri = $body['postLogoutRedirectUri'] ?? null;

        if (!is_string($idToken) || $idToken === '') {
            return new ApiProblem(400, 'idToken must be provided');
        }
        if (!is_string($postLogoutRedirectUri) || $postLogoutRedirectUri === '') {
            return new ApiProblem(400, 'postLogoutRedirectUri must be provided');
        }

        TelemetryEventManager::triggerStart('OneLoginController.logoutAction');

        try {
            $url = $this->getService()->createLogoutRequest($idToken, $postLogoutRedirectUri);
        } finally {
            TelemetryEventManager::triggerStop();
        }

        return new JsonModel(['url' => $url]);
    }

    /**
     * @return JsonModel
     */
    public function backChannelLogoutAction(): JsonModel
    {
        /** @var mixed $body */
        $body = json_decode((string) $this->getRequest()->getContent(), true);

        $logoutToken = is_array($body) ? ($body['logoutToken'] ?? null) : null;

        if (!is_string($logoutToken) || $logoutToken === '') {
            return new JsonModel(['accepted' => false, 'reason' => 'missing_logout_token']);
        }

        TelemetryEventManager::triggerStart('OneLoginController.backChannelLogoutAction');

        try {
            $result = $this->getService()->handleBackChannelLogout($logoutToken);
        } finally {
            TelemetryEventManager::triggerStop();
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel|ApiProblem
     */
    public function createAction(): JsonModel|ApiProblem
    {
        /** @var array{oneLoginSub: string, oneLoginEmail: string, userType?: mixed, profile?: mixed} $body */
        $body = json_decode((string) $this->getRequest()->getContent(), true);

        $userType = $this->userType($body['userType'] ?? null);

        if ($userType === null) {
            return new ApiProblem(400, 'userType must be one of: lay, professional');
        }

        $profile = $body['profile'] ?? null;

        if (!is_array($profile)) {
            return new ApiProblem(400, 'profile must be provided');
        }

        TelemetryEventManager::triggerStart('OneLoginController.createAction');

        try {
            $result = $this->getService()->createAndLinkAccount(
                $body['oneLoginSub'],
                $body['oneLoginEmail'],
                $userType,
                $profile,
            );
        } catch (InvalidArgumentException $e) {
            return new ApiProblem(400, $e->getMessage());
        } finally {
            TelemetryEventManager::triggerStop();
        }

        return new JsonModel($result);
    }

    private function userType(mixed $value): ?UserType
    {
        return is_string($value) ? UserType::tryFrom($value) : null;
    }
}
