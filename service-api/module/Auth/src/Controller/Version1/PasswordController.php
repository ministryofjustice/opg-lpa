<?php
namespace Auth\Controller\Version1;

use Auth\Model\Service\AuthenticationService;
use Auth\Model\Service\PasswordChangeService;
use Auth\Model\Service\PasswordResetService;
use Opg\Lpa\Logger\LoggerTrait;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use Zend\View\Model\JsonModel;

class PasswordController extends AbstractAuthenticatedController
{
    use LoggerTrait;

    /**
     * @var PasswordChangeService
     */
    private $passwordChangeService;

    /**
     * @var PasswordResetService
     */
    private $passwordResetService;

    public function __construct(
        AuthenticationService $authenticationService,
        PasswordChangeService $passwordChangeService,
        PasswordResetService $passwordResetService
    ) {
        parent::__construct($authenticationService);
        $this->passwordChangeService = $passwordChangeService;
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Change the user's password; and then automatically re-logs them in again.
     * i.e. it returns a new valid auth token.
     */
    public function changeAction(){

        $userId = $this->params('userId');

        $currentPassword = $this->getRequest()->getPost( 'CurrentPassword' );
        $newPassword = $this->getRequest()->getPost( 'NewPassword' );

        //---

        if( empty($currentPassword) || empty($newPassword) ){

            // Token and/or userId not passed
            return new ApiProblemResponse(
                new ApiProblem(400, 'Missing Current Password and/or New Password')
            );

        }

        //---

        if (!$this->authenticateUserToken($this->getRequest(), $userId)) {
            return new ApiProblemResponse(
                new ApiProblem(401, 'invalid-token')
            );
        }

        //---

        $result = $this->passwordChangeService->changePassword(
            $userId, $currentPassword, $newPassword
        );

        if( is_string($result) ){
            return new ApiProblemResponse(
                new ApiProblem(401, $result)
            );
        }

        //---

        $this->getLogger()->info("User successfully change their password",[
            'userId' => $userId
        ]);

        //---

        // Map DateTimes to strings
        $result = array_map( function($v) {
            return ( $v instanceof \DateTime ) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $result);

        return new JsonModel( $result );

    }

    public function passwordResetAction() {

        $username = $this->getRequest()->getPost( 'Username' );

        if ( empty($username) ) {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Username must be passed')
            );
        }

        $result = $this->passwordResetService->generateToken($username);

        if ($result == 'user-not-found') {

            $this->getLogger()->notice("Password reset request for unknown user",[
                'username' => $username
            ]);

            //---

            return new ApiProblemResponse(
                new ApiProblem(404, 'User not found')
            );

        } // if

        //---

        // Map DateTimes to strings
        $result = array_map( function($v) {
            return ( $v instanceof \DateTime ) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $result);

        //---

        //  Determine the token value for the logging message
        $token = (isset($result['activation_token']) ? $result['activation_token'] : $result['token']);

        $this->getLogger()->info("Password reset token requested",[
            'token' => $token,
            'username' => $username
        ]);

        //---

        return new JsonModel( $result );

    }

    /**
     * Update user password following password reset request
     *
     * @throws \ZF\ApiProblem\ApiProblemResponse
     */
    public function passwordResetUpdateAction(){

        $token = $this->getRequest()->getPost( 'Token' );
        $newPassword = $this->getRequest()->getPost( 'NewPassword' );

        if (empty($token)) {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Token required')
            );
        }

        $result = $this->passwordResetService->updatePasswordUsingToken($token, $newPassword);

        if ($result === 'invalid-token') {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Invalid token')
            );
        }

        if ($result === 'invalid-password') {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Invalid password')
            );
        }

        //---

        $this->getLogger()->info("User successfully change their password via a reset",[
            'token' => $token
        ]);

        //---

        // Return 204 - No Content
        $this->response->setStatusCode(204);
    }

}