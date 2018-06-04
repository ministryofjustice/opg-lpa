<?php

namespace Auth\Controller\Version1;

use Auth\Model\Service\UserManagementService;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\View\Model\JsonModel;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class UsersController extends AbstractAuthenticatedController
{
    use LoggerTrait;

    /**
     * @var UserManagementService
     */
    private $userManagementService;

    /**
     * Returns the details for the passed userId
     *
     * @return JsonModel|ApiProblemResponse
     */
    public function indexAction()
    {
        $userId = $this->params('userId');

        // Authenticate the user id...
        if ($this->authenticateUserToken($this->getRequest(), $userId, true) === false) {
            //Token does not match userId
            return new ApiProblemResponse(
                new ApiProblem(401, 'invalid-token')
            );
        }

        // Get and return the user...
        $user = $this->userManagementService->get($userId);

        // Map DateTimes to strings
        $user = array_map(function ($v) {
            return ( $v instanceof \DateTime ) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $user);

        return new JsonModel($user);
    }

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
