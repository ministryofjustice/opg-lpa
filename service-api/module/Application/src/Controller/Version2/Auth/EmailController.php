<?php

namespace Application\Controller\Version2\Auth;

use Auth\Model\Service\EmailUpdateService as Service;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\View\Model\JsonModel;
use ZF\ApiProblem\ApiProblem;

class EmailController extends AbstractAuthController
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
     * Change the user email address
     *
     * NOTE: This action does not actually change the email value by itself
     * It will set the new value in an unverified state, then the function below must be called to complete the change
     *
     * @return JsonModel|ApiProblem
     */
    public function changeAction()
    {
        $userId = $this->params('userId');

        $newEmailAddress = $this->getBodyContent('newEmail');

        if (empty($newEmailAddress)) {
            return new ApiProblem(400, 'Email address must be passed');
        }

        if (!$this->authenticateUserToken($this->getRequest(), $userId)) {
            return new ApiProblem(401, 'invalid-token');
        }

        $result = $this->service->generateToken($userId, $newEmailAddress);

        if ($result === 'invalid-email') {
            return new ApiProblem(400, 'Invalid email address');
        }

        if ($result === 'user-not-found') {
            return new ApiProblem(404, 'User not found');
        }

        if ($result === 'username-already-exists') {
            return new ApiProblem(400, 'Email already exists for another user');
        }

        if ($result === 'username-same-as-current') {
            return new ApiProblem(400, 'User already has this email');
        }

        // Map DateTimes to strings
        $result = array_map(function ($v) {
            return ( $v instanceof \DateTime ) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $result);

        $this->getLogger()->info("User successfully requested update email token", [
            'userId' => $userId
        ]);

        return new JsonModel($result);
    }

    /**
     * Use a token value to verify a new email address
     *
     * @return JsonModel|ApiProblem
     */
    public function verifyAction()
    {
        $emailUpdateToken = $this->getBodyContent('emailUpdateToken');

        if (empty($emailUpdateToken)) {
            return new ApiProblem(400, 'Token must be passed');
        }

        $result = $this->service->updateEmailUsingToken($emailUpdateToken);

        if ($result === 'invalid-token') {
            return new ApiProblem(404, 'Invalid token');
        }

        if ($result === 'username-already-exists') {
            return new ApiProblem(400, 'Email already exists for another user');
        }

        if ($result === false) {
            return new ApiProblem(500, 'Unable to update email address');
        }

        $this->getLogger()->info("User successfully update email with token", [
            'userId' => $result->id()
        ]);

        // Return 204 - No Content
        $this->response->setStatusCode(204);

        return new JsonModel();
    }
}
