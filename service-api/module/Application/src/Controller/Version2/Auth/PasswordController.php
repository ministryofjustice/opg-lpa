<?php

namespace Application\Controller\Version2\Auth;

use Auth\Model\Service\PasswordService;
use Opg\Lpa\Logger\LoggerTrait;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use Zend\View\Model\JsonModel;

class PasswordController extends AbstractAuthenticatedController
{
    use LoggerTrait;

    /**
     * @var PasswordService
     */
    private $passwordService;

    /**
     * Change the user's password; and then automatically re-logs them in again.
     * i.e. it returns a new valid auth token.
     *
     * @return JsonModel|ApiProblemResponse
     */
    public function changeAction()
    {
        $userId = $this->params('userId');

        $currentPassword = $this->getRequest()->getPost('CurrentPassword');
        $newPassword = $this->getRequest()->getPost('NewPassword');

        if (empty($currentPassword) || empty($newPassword)) {
            // Token and/or userId not passed
            return new ApiProblemResponse(
                new ApiProblem(400, 'Missing Current Password and/or New Password')
            );
        }

        if (!$this->authenticateUserToken($this->getRequest(), $userId)) {
            return new ApiProblemResponse(
                new ApiProblem(401, 'invalid-token')
            );
        }

        $result = $this->passwordService->changePassword($userId, $currentPassword, $newPassword);

        if (is_string($result)) {
            return new ApiProblemResponse(
                new ApiProblem(401, $result)
            );
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
     * @return JsonModel|ApiProblemResponse
     */
    public function passwordResetAction()
    {
        $username = $this->getRequest()->getPost('Username');

        if (empty($username)) {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Username must be passed')
            );
        }

        $result = $this->passwordService->generateToken($username);

        if ($result == 'user-not-found') {
            $this->getLogger()->notice("Password reset request for unknown user", [
                'username' => $username
            ]);

            return new ApiProblemResponse(
                new ApiProblem(404, 'User not found')
            );
        }

        // Map DateTimes to strings
        $result = array_map(function ($v) {
            return ( $v instanceof \DateTime ) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $result);

        //  Determine the token value for the logging message
        $token = (isset($result['activation_token']) ? $result['activation_token'] : $result['token']);

        $this->getLogger()->info("Password reset token requested", [
            'token' => $token,
            'username' => $username
        ]);

        return new JsonModel($result);
    }

    /**
     * Update user password following password reset request
     *
     * @return ApiProblemResponse
     */
    public function passwordResetUpdateAction()
    {
        $token = $this->getRequest()->getPost('Token');
        $newPassword = $this->getRequest()->getPost('NewPassword');

        if (empty($token)) {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Token required')
            );
        }

        $result = $this->passwordService->updatePasswordUsingToken($token, $newPassword);

        if ($result === 'invalid-token') {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Invalid token')
            );
        }

        if ($result === 'invalid-password') {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Invalid password')
            );
        }

        $this->getLogger()->info("User successfully change their password via a reset", [
            'token' => $token
        ]);

        // Return 204 - No Content
        $this->response->setStatusCode(204);
    }

    /**
     * @param PasswordService $passwordService
     */
    public function setPasswordService(PasswordService $passwordService)
    {
        $this->passwordService = $passwordService;
    }
}
