<?php

namespace Application\Controller\Version2\Auth;

use Auth\Model\Service\UserManagementService as Service;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\View\Model\JsonModel;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class UsersController extends AbstractController
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
    public function searchAction()
    {
        $email = $this->params()->fromQuery()['email'];

        $user = $this->service->getByUsername($email);

        if ($user === false) {
            return new ApiProblemResponse(
                new ApiProblem(404, 'No user found with supplied email address')
            );
        }

        return new JsonModel($user);
    }
}
