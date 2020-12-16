<?php

namespace Application\Controller\Version2\Auth;

use Application\Model\Service\Users\Service;
use Laminas\View\Model\JsonModel;
use Laminas\ApiTools\ApiProblem\ApiProblem;

class UsersController extends AbstractAuthController
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
        $result = $this->getService()->create($username, $password);

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
        $result = $this->getService()->activate($activationToken);

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

        $user = $this->getService()->searchByUsername($email);

        if ($user === false) {
            return new ApiProblem(404, 'No user found with supplied email address');
        }

        return new JsonModel($user);
    }

    /**
     * Match action for user details (wildcard/case-insensitive search)
     *
     * @return JsonModel
     */
    public function matchAction()
    {
        $params = $this->params();
        $query = $params->fromQuery('query');

        $options = [
            'offset' => $params->fromQuery('offset', 0),
            'limit' => $params->fromQuery('limit', 10)
        ];

        $users = $this->service->matchUsers($query, $options);

        return new JsonModel($users);
    }
}
