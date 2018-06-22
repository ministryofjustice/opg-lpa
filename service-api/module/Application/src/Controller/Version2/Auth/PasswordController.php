<?php

namespace Application\Controller\Version2\Auth;

use Auth\Model\Service\PasswordService as Service;
use Opg\Lpa\Logger\LoggerTrait;
use ZF\ApiProblem\ApiProblem;
use Zend\View\Model\JsonModel;

class PasswordController extends AbstractController
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
        $userId = $this->params('userId');

        if (empty($userId)) {
            return $this->changeWithToken();
        }

        return $this->changeWithPassword($userId);
    }

    /**
     * Change the user password by providing the existing password
     * NOTE: This also re-executes the login so that the calling function has access to a new authentication token
     *
     * @param $userId
     * @return JsonModel|ApiProblem
     */
    private function changeWithPassword($userId)
    {
        $currentPassword = $this->getRequest()->getPost('currentPassword');
        $newPassword = $this->getRequest()->getPost('newPassword');

        if (empty($currentPassword) || empty($newPassword)) {
            // Token and/or userId not passed
            return new ApiProblem(400, 'Missing Current Password and/or New Password');
        }

        if (!$this->authenticateUserToken($this->getRequest(), $userId)) {
            return new ApiProblem(401, 'invalid-token');
        }

        $result = $this->service->changePassword($userId, $currentPassword, $newPassword);

        if (is_string($result)) {
            return new ApiProblem(401, $result);
        }

        $this->getLogger()->info("User successfully change their password", [
            'userId' => $userId
        ]);

        // Map DateTimes to strings
        $result = array_map(function ($v) {
            return ( $v instanceof \DateTime ) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $result);

        return new JsonModel($result);
    }

    /**
     * Change the user password by providing password token
     *
     * @return ApiProblem
     */
    private function changeWithToken()
    {
        $passwordToken = $this->getRequest()->getPost('passwordToken');
        $newPassword = $this->getRequest()->getPost('newPassword');

        if (empty($passwordToken)) {
            return new ApiProblem(400, 'token required');
        }

        $result = $this->service->updatePasswordUsingToken($passwordToken, $newPassword);

        if ($result === 'invalid-token') {
            return new ApiProblem(400, 'Invalid passwordToken');
        }

        if ($result === 'invalid-password') {
            return new ApiProblem(400, 'Invalid password');
        }

        $this->getLogger()->info("User successfully change their password via a reset", [
            'passwordToken' => $passwordToken
        ]);

        // Return 204 - No Content
        $this->response->setStatusCode(204);
    }

    /**
     * @return JsonModel|ApiProblem
     */
    public function resetAction()
    {
        $username = $this->getRequest()->getPost('username');

        if (empty($username)) {
            return new ApiProblem(400, 'username must be passed');
        }

        $result = $this->service->generateToken($username);

        if ($result == 'user-not-found') {
            $this->getLogger()->notice("Password reset request for unknown user", [
                'username' => $username
            ]);

            return new ApiProblem(404, 'User not found');
        }

        // Map DateTimes to strings
        $result = array_map(function ($v) {
            return ( $v instanceof \DateTime ) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $result);

        //  Determine the token value for the logging message
        $token = (isset($result['activation_token']) ? $result['activation_token'] : $result['token']);

        $this->getLogger()->info("Password reset token requested", [
            'token'    => $token,
            'username' => $username
        ]);

        return new JsonModel($result);
    }
}
