<?php

namespace Application\Controller\Version2\Auth;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\Users\Service;
use Laminas\View\Model\JsonModel;
use MakeShared\Logging\LoggerTrait;
use Random\RandomException;

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
     * @throws RandomException
     */
    private function createAccount(string $username, $password)
    {
        $result = $this->getService()->create($username, $password);

        if (is_string($result)) {
            return new ApiProblem(400, $result);
        }

        $this->getLogger()->info('New user account created', $result);

        return new JsonModel($result);
    }

    /**
     * @param $activationToken
     * @return JsonModel|ApiProblem
     */
    private function activateAccount(string $activationToken)
    {
        $result = $this->getService()->activate($activationToken);

        if (is_string($result)) {
            return new ApiProblem(400, $result);
        }

        $this->getLogger()->info('New user account activated', [
            'activation_token' => $activationToken
        ]);

        // Return 204 - No Content
        // Note: The Laminas AbstractRestfulController response member
        // variable is an instance of Laminas\Stdlib\ResponseInterface, which doesn't
        // have a setStatusCode() method. However, in the getResponse() method of
        // AbstractRestfulController, we see this member variable being populated
        // with a Laminas\Http\Response instance, which does.
        // psalm doesn't like this, which is why we mark this as "ignored", until
        // Laminas fixes the bug - see https://github.com/laminas/laminas-mvc/issues/77
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
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
