<?php

namespace Auth\Model\Service;

use Auth\Model\Service\DataAccess\LogDataSourceInterface;
use Auth\Model\Service\DataAccess\UserDataSourceInterface;

class PasswordChangeService extends AbstractService
{
    use PasswordValidatorTrait;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    public function __construct(
        UserDataSourceInterface $userDataSource,
        LogDataSourceInterface $logDataSource,
        AuthenticationService $authenticationService
    ) {
        parent::__construct($userDataSource, $logDataSource);

        $this->authenticationService = $authenticationService;
    }

    //-------------

    public function changePassword( $userId, $oldPassword, $newPassword ){

        $user = $this->getUserDataSource()->getById( $userId );

        if( is_null( $user ) ){
            return 'user-not-found';
        }

        //---

        // Ensure the new password is valid
        if ( !$this->isPasswordValid( $newPassword ) ) {
            return 'invalid-new-password';
        }

        //---

        // Ensure the old password is valid
        if( !password_verify($oldPassword , $user->password()) ){
            return 'invalid-user-credentials';
        }

        //---

        $passwordHash = password_hash( $newPassword, PASSWORD_DEFAULT );

        $this->getUserDataSource()->setNewPassword($user->id(), $passwordHash);

        //---

        return $this->authenticationService->withPassword(
            $user->username(), $newPassword, true
        );

    } // function

} // class
