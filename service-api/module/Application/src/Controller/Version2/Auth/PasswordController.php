<?php

namespace Application\Controller\Version2\Auth;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\Password\Service;
use DateTime;
use Laminas\View\Model\JsonModel;
use MakeShared\Logging\LoggerTrait;
use Random\RandomException;

class PasswordController extends AbstractAuthController
{
    use LoggerTrait;

    /**
     * Get the service to use
     *
     * @return Service
     */
    protected function getService()
    {
        return $this->service;
    }

    /**
     * Change the user password either by providing the existing password or a password token
     *
     * @return JsonModel|ApiProblem
     */
    public function changeAction()
    {
        $userId = $this->params()->fromRoute('userId');

        $newPassword = $this->getBodyContent('newPassword');

        if (empty($newPassword)) {
            return new ApiProblem(400, 'Missing New Password');
        }

        if (!empty($userId)) {
            $currentPassword = $this->getBodyContent('currentPassword');

            if (empty($currentPassword)) {
                return new ApiProblem(400, 'Missing Current Password');
            }

            return $this->changeWithPassword($userId, $currentPassword, $newPassword);
        }

        //  Change the password using a token value
        $passwordToken = $this->getBodyContent('passwordToken');

        if (empty($passwordToken)) {
            return new ApiProblem(400, 'token required');
        }

        return $this->changeWithToken($passwordToken, $newPassword);
    }

    /**
     * Change the user password by providing the existing password
     * NOTE: This also re-executes the login so that the calling function has access to a new authentication token
     *
     * @param $userId
     * @param $currentPassword
     * @param $newPassword
     * @return JsonModel|ApiProblem
     */
    private function changeWithPassword($userId, $currentPassword, $newPassword)
    {
        if (!$this->authenticateUserToken($userId)) {
            return new ApiProblem(401, 'invalid-token');
        }

        $result = $this->getService()->changePassword($userId, $currentPassword, $newPassword);

        if (is_string($result)) {
            return new ApiProblem(401, $result);
        }

        $this->getLogger()->info('User successfully change their password', [
            'userId' => $userId
        ]);

        // Map DateTimes to strings
        $result = array_map(function ($v) {
            return ($v instanceof DateTime ? $v->format('Y-m-d\TH:i:sO') : $v);
        }, $result);

        return new JsonModel($result);
    }

    /**
     * Change the user password by providing password token
     *
     * @param $passwordToken
     * @param $newPassword
     * @return JsonModel|ApiProblem
     */
    private function changeWithToken($passwordToken, $newPassword)
    {
        $result = $this->getService()->updatePasswordUsingToken($passwordToken, $newPassword);

        if ($result === 'invalid-token') {
            return new ApiProblem(400, 'Invalid passwordToken');
        }

        if ($result === 'invalid-password') {
            return new ApiProblem(400, 'Invalid password');
        }

        if (is_string($result)) {
            return new ApiProblem(400, "Unknown error: {$result}");
        }

        $this->getLogger()->info('User successfully change their password via a reset');

        //  Return 204 - No Content
        // Suppress psalm errors caused by bug in laminas-mvc;
        // see https://github.com/laminas/laminas-mvc/issues/77
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $this->response->setStatusCode(204);

        return new JsonModel();
    }

    /**
     * @return JsonModel|ApiProblem
     * @throws RandomException
     */
    public function resetAction()
    {
        $username = $this->getBodyContent('username');

        if (empty($username)) {
            return new ApiProblem(400, 'username must be passed');
        }

        $result = $this->getService()->generateToken($username);

        if ($result == 'user-not-found') {
            $this->getLogger()->warning('Password reset request for unknown user', [
                'username' => $username
            ]);

            return new ApiProblem(404, 'User not found');
        }

        // Map DateTimes to strings
        $result = array_map(function ($v) {
            return ($v instanceof \DateTime) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $result);

        //  Determine the token value for the logging message
        $token = (isset($result['activation_token']) ? $result['activation_token'] : $result['token']);

        $this->getLogger()->debug('Password reset token requested', [
            'token'    => $token,
            'username' => $username
        ]);

        return new JsonModel($result);
    }
}
