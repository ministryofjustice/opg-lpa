<?php

namespace Application\Controller\Version2\Auth;

use Auth\Model\Service\EmailUpdateService as Service;
use Opg\Lpa\Logger\LoggerTrait;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use Zend\View\Model\JsonModel;

class EmailController extends AbstractController
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
     * @return JsonModel|ApiProblemResponse
     */
    public function getEmailUpdateTokenAction()
    {
        $userId = $this->params('userId');
        $newEmail = $this->params('newEmail');

        if (!$this->authenticateUserToken($this->getRequest(), $userId)) {
            return new ApiProblemResponse(
                new ApiProblem(401, 'invalid-token')
            );
        }

        $result = $this->service->generateToken($userId, $newEmail);

        if ($result === 'invalid-email') {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Invalid email address')
            );
        }

        if ($result === 'user-not-found') {
            return new ApiProblemResponse(
                new ApiProblem(404, 'User not found')
            );
        }

        if ($result === 'username-already-exists') {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Email already exists for another user')
            );
        }

        if ($result === 'username-same-as-current') {
            return new ApiProblemResponse(
                new ApiProblem(400, 'User already has this email')
            );
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
     * @return ApiProblemResponse
     */
    public function updateEmailAction()
    {
        $emailUpdateToken = $this->getRequest()->getPost('Token');

        if (is_null($emailUpdateToken)) {
            // Check for the old parameter name
            $emailUpdateToken = $this->getRequest()->getPost('emailUpdateToken');
        }

        if (empty($emailUpdateToken)) {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Token must be passed')
            );
        }

        $result = $this->service->updateEmailUsingToken($emailUpdateToken);

        if ($result === 'invalid-token') {
            return new ApiProblemResponse(
                new ApiProblem(404, 'Invalid token')
            );
        }

        if ($result === 'username-already-exists') {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Email already exists for another user')
            );
        }

        if ($result === false) {
            return new ApiProblemResponse(
                new ApiProblem(500, 'Unable to update email address')
            );
        }

        $this->getLogger()->info("User successfully update email with token", [
            'userId' => $result->id()
        ]);

        // Return 204 - No Content
        $this->response->setStatusCode(204);
    }
}
