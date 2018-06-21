<?php

namespace Application\Controller\Version2\Auth;

use Auth\Model\Service\RegistrationService as Service;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\View\Model\JsonModel;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class RegistrationController extends AbstractController
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
    public function createAction()
    {
        $params = $this->getRequest()->getPost();

        if (!(isset($params['Username']) && isset($params['Password']))) {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Username and Password must be passed')
            );
        }

        $result = $this->service->create($params['Username'], $params['Password']);

        if (is_string($result)) {
            return new ApiProblemResponse(
                new ApiProblem(400, $result)
            );
        }

        $this->getLogger()->info("New user account created", $result);

        return new JsonModel($result);
    }

    /**
     * @return ApiProblemResponse
     */
    public function activateAction()
    {
        $token = $this->getRequest()->getPost('Token');

        if (empty($token)) {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Token must be passed')
            );
        }

        $result = $this->service->activate($token);

        if (is_string($result)) {
            return new ApiProblemResponse(
                new ApiProblem(400, $result)
            );
        }

        $this->getLogger()->info("New user account activated", [
            'activation_token' => $token
        ]);

        // Return 204 - No Content
        $this->response->setStatusCode(204);
    }
}
