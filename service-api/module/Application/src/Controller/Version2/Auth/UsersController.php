<?php

namespace Application\Controller\Version2\Auth;

use Auth\Model\Service\UserManagementService;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\View\Model\JsonModel;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class UsersController extends AbstractController
{
    use LoggerTrait;

    /**
     * @var UserManagementService
     */
    private $userManagementService;

    /**
     * @return JsonModel|ApiProblemResponse
     */
    public function searchAction()
    {
        $email = $this->params()->fromQuery()['email'];

        $user = $this->userManagementService->getByUsername($email);

        if ($user === false) {
            return new ApiProblemResponse(
                new ApiProblem(404, 'No user found with supplied email address')
            );
        }

        return new JsonModel($user);
    }

    /**
     * @param UserManagementService $userManagementService
     */
    public function setUserManagementService(UserManagementService $userManagementService)
    {
        $this->userManagementService = $userManagementService;
    }
}
