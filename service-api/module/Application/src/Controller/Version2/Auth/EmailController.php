<?php

namespace Application\Controller\Version2\Auth;

use Application\Model\Service\Email\Service;
use Laminas\View\Model\JsonModel;
use Laminas\ApiTools\ApiProblem\ApiProblem;

class EmailController extends AbstractAuthController
{
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
        $userId = $this->params()->fromRoute('userId');

        $newEmailAddress = $this->getBodyContent('newEmail');

        if (empty($newEmailAddress)) {
            return new ApiProblem(400, 'Email address must be passed');
        }

        if (!$this->authenticateUserToken($this->getRequest(), $userId)) {
            return new ApiProblem(401, 'invalid-token');
        }

        $result = $this->getService()->generateToken($userId, $newEmailAddress);

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
            return ($v instanceof \DateTime) ? $v->format('Y-m-d\TH:i:sO') : $v;
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

        $result = $this->getService()->updateEmailUsingToken($emailUpdateToken);

        if ($result->error()) {
            if ($result->message() === 'invalid-token') {
                return new ApiProblem(404, 'Invalid token');
            }

            if ($result->message() === 'username-already-exists') {
                return new ApiProblem(400, 'Email already exists for another user');
            }

            return new ApiProblem(500, 'Unable to update email address');
        }


        $this->getLogger()->info("User successfully update email with token", [
            'userId' => $result->getUser()->id()
        ]);

        // Return 204 - No Content
        // Suppress psalm errors caused by bug in laminas-mvc;
        // see https://github.com/laminas/laminas-mvc/issues/77
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $this->response->setStatusCode(204);

        return new JsonModel();
    }
}
