<?php

namespace Application\Controller\Version2\Auth;

use Zend\View\Model\JsonModel;
use ZF\ApiProblem\ApiProblem;

class AuthenticateController extends AbstractAuthController
{
    /**
     * TODO - Refactor later...
     * NOTE - Present to satisfy requirement in AbstractAuthController
     *
     * @return null
     */
    protected function getService()
    {
        return null;
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
}
