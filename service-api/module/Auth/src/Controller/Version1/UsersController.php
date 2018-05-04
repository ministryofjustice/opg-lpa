<?php
namespace Auth\Controller\Version1;

use Auth\Model\Service\AuthenticationService;
use Auth\Model\Service\UserManagementService;
use Opg\Lpa\Logger\LoggerTrait;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use Zend\View\Model\JsonModel;

class UsersController extends AbstractAuthenticatedController
{
    use LoggerTrait;

    /**
     * @var UserManagementService
     */
    private $userManagementService;

    public function __construct(
        AuthenticationService $authenticationService,
        UserManagementService $userManagementService
    ) {
        parent::__construct($authenticationService);

        $this->userManagementService = $userManagementService;
    }

    /**
     * Returns the details for the passed userId
     *
     * @return JsonModel|ApiProblemResponse
     */
    public function indexAction(){

        $userId = $this->params('userId');

        //---------------------------------
        // Authenticate the user id...

        if ($this->authenticateUserToken($this->getRequest(), $userId, true) === false) {
            //Token does not match userId
            return new ApiProblemResponse(
                new ApiProblem(401, 'invalid-token')
            );
        }

        //---------------------------------§
        // Get and return the user...

        $user = $this->userManagementService->get( $userId );

        // Map DateTimes to strings
        $user = array_map( function($v) {
            return ( $v instanceof \DateTime ) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $user);

        //---

        return new JsonModel( $user );

    } // function

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

    public function deleteAction(){

        $userId = $this->params('userId');

        //---------------------------------
        // Authenticate the token...

        if ($this->authenticateUserToken($this->getRequest(), $userId, true) === false) {
            //Token does not match userId
            return new ApiProblemResponse(
                new ApiProblem(401, 'invalid-token')
            );
        }

        //---------------------------------
        // Delete the user

        $result = $this->userManagementService->delete( $userId, 'user-initiated' );

        if( is_string($result) ){
            return new ApiProblemResponse(
                new ApiProblem(400, $result)
            );
        }

        //---

        $this->getLogger()->info("User has deleted their account",[
            'userId' => $userId
        ]);

        //---

        // Return 204 - No Content
        $this->response->setStatusCode(204);

    }

} // class
