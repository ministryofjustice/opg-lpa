<?php

namespace Application\Controller\Version2\Auth;

use Application\Model\Service\UserManagement\Service;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\View\Model\JsonModel;
use ZF\ApiProblem\ApiProblem;

class UsersController extends AbstractAuthController
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
     * @param mixed $data
     * @return JsonModel|ApiProblem
     */
    public function create($data)
    {
        if (isset($data['activationToken'])) {
            return $this->activateAccount(trim($data['activationToken']));
        } elseif (isset($data['username']) && isset($data['password'])) {
            return $this->createAccount(trim($data['username']), $data['password']);
        }

        return new ApiProblem(400, 'Either activationToken or username & password must be passed');
    }

    /**
     * @param $username
     * @param $password
     * @return JsonModel|ApiProblem
     */
    private function createAccount($username, $password)
    {
        $result = $this->service->create($username, $password);

        if (is_string($result)) {
            return new ApiProblem(400, $result);
        }

        $this->getLogger()->info("New user account created", $result);

        return new JsonModel($result);
    }

    /**
     * @param $activationToken
     * @return JsonModel|ApiProblem
     */
    private function activateAccount($activationToken)
    {
        $result = $this->service->activate($activationToken);

        if (is_string($result)) {
            return new ApiProblem(400, $result);
        }

        $this->getLogger()->info("New user account activated", [
            'activation_token' => $activationToken
        ]);

        // Return 204 - No Content
        $this->response->setStatusCode(204);

        return new JsonModel();
    }

    /**
     * Search action for user details
     * NOTE: Custom action method has been used here because 'get' can not be used without an ID value in the URL target
     *
     * @return JsonModel|ApiProblem
     */
    public function searchAction()
    {
        $email = $this->params()->fromQuery()['email'];

        $user = $this->service->getByUsername($email);

        if ($user === false) {
            return new ApiProblem(404, 'No user found with supplied email address');
        }

        return new JsonModel($user);
    }
}
