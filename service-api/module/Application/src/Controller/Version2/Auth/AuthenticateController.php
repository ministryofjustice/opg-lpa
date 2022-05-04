<?php

namespace Application\Controller\Version2\Auth;

use DateTime;
use Application\Model\Service\AbstractService;
use Laminas\View\Model\JsonModel;
use Laminas\ApiTools\ApiProblem\ApiProblem;
use RuntimeException;

class AuthenticateController extends AbstractAuthController
{
    /**
     * @return AbstractService
     */
    protected function getService()
    {
        throw new RuntimeException('getService method not implemented for AuthenticateController');
    }

    /**
     * @return JsonModel|ApiProblem
     */
    public function authenticateAction()
    {
        $data = $this->getBodyContent();

        $updateToken = (isset($data['Update']) && $data['Update'] === 'false' ? false : true);

        if (isset($data['authToken'])) {
            return $this->withToken(trim($data['authToken']), $updateToken);
        } elseif (isset($data['username']) && isset($data['password'])) {
            return $this->withPassword(trim($data['username']), $data['password'], $updateToken);
        }

        return new ApiProblem(400, 'Either token or username & password must be passed');
    }

    /**
     * Authenticate a user with a passed authToken.
     *
     * @param $authToken
     * @param $updateToken
     * @return JsonModel|ApiProblem
     */
    private function withToken($authToken, $updateToken)
    {
        $result = $this->authenticationService->withToken($authToken, $updateToken);

        if (is_string($result)) {
            $this->getLogger()->notice("Failed authentication attempt with a authToken", [
                'authToken' => $authToken
            ]);

            return new ApiProblem(401, $result);
        }

        // Map DateTimes to strings
        $result = array_map(function ($v) {
            return ($v instanceof \DateTime ? $v->format('Y-m-d\TH:i:sO') : $v);
        }, $result);

        $this->getLogger()->info("User successfully authenticated with a authToken", [
            'tokenExtended' => (bool)$updateToken,
            'userId'        => $result['userId'],
            'expiresAt'     => $result['expiresAt'],
        ]);

        return new JsonModel($result);
    }

    /**
     * Authenticate a user with a passed username (email address) and password.
     *
     * @param $username
     * @param $password
     * @param $updateToken
     * @return JsonModel|ApiProblem
     */
    private function withPassword($username, $password, $updateToken)
    {
        $result = $this->authenticationService->withPassword($username, $password, $updateToken);

        if (is_string($result)) {
            $this->getLogger()->notice("Failed authentication attempt with a password", [
                'username' => $username
            ]);

            return new ApiProblem(401, $result);
        }

        // Map DateTimes to strings
        $result = array_map(function ($v) {
            return ($v instanceof \DateTime ? $v->format('Y-m-d\TH:i:sO') : $v);
        }, $result);

        $this->getLogger()->info("User successfully authenticated with a password", [
            'userId'     => $result['userId'],
            'last_login' => $result['last_login'],
            'expiresAt'  => $result['expiresAt'],
        ]);

        return new JsonModel($result);
    }

    /**
     * The token to be is in the header rather than the URL so that it is encrypted by ssl and still conforms
     * to being a standard GET request (no body)
     *
     * @return JsonModel|ApiProblem
     */
    public function sessionExpiryAction()
    {
        // Suppress psalm errors caused by bug in laminas-mvc;
        // see https://github.com/laminas/laminas-mvc/issues/77
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $token = $this->getRequest()->getHeader('CheckedToken');

        if ($token == null) {
            return new ApiProblem(400, 'No CheckedToken was specified in the header');
        }

        $result = $this->authenticationService->withToken(trim($token->getFieldValue()), false);

        if ($result instanceof ApiProblem) {
            return $result;
        }

        if (is_string($result)) {
            return new JsonModel(['valid' => false, 'problem' => $result]);
        }

        return new JsonModel(['valid' => true, 'remainingSeconds' => $result['expiresIn']]);
    }

    /**
     * expects a JSON POST with the following properties:
     * - CheckedToken header, containing the user's auth token
     * - JSON body with these properties:
     *   - "expiresInSeconds": <int>
     */
    public function setSessionExpiryAction()
    {
        // Suppress psalm errors caused by bug in laminas-mvc;
        // see https://github.com/laminas/laminas-mvc/issues/77
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $token = $this->getRequest()->getHeader('CheckedToken');

        if ($token == null) {
            return new ApiProblem(400, 'No CheckedToken was specified in the header');
        }

        // create datetime by getting the expiry time in seconds from the POST
        $expireInSeconds = $this->getBodyContent('expireInSeconds');
        if ($expireInSeconds === null) {
            return new ApiProblem(400, 'No expireInSeconds property in JSON request body');
        }

        $tokenStr = trim($token->getFieldValue());
        $needsUpdate = true;
        $throttle = false;
        $expiresAt = (new DateTime())->modify('+' . $expireInSeconds . ' seconds');

        $result = $this->authenticationService->updateToken($tokenStr, $needsUpdate, $throttle, $expiresAt);

        if (is_string($result)) {
            return new JsonModel(['valid' => false, 'problem' => $result]);
        }

        return new JsonModel(['valid' => true, 'remainingSeconds' => $result['expiresIn']]);
    }
}
