<?php

namespace Application\Model\Service\Password;

use Application\Model\DataAccess\Repository\Auth\UserRepositoryTrait;
use Application\Model\DataAccess\Repository\Auth\UserInterface as User;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\PasswordValidatorTrait;
use Zend\Math\BigInteger\BigInteger;
use DateTime;
use RuntimeException;

class Service extends AbstractService
{
    use PasswordValidatorTrait;
    use UserRepositoryTrait;

    const TOKEN_TTL = 86400; // 24 hours

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @param $userId
     * @param $oldPassword
     * @param $newPassword
     * @return array|string
     */
    public function changePassword($userId, $oldPassword, $newPassword)
    {
        $user = $this->getUserRepository()->getById($userId);

        if (is_null($user)) {
            return 'user-not-found';
        }

        // Ensure the new password is valid
        if (!$this->isPasswordValid($newPassword)) {
            return 'invalid-new-password';
        }

        // Ensure the old password is valid
        if (!password_verify($oldPassword, $user->password())) {
            return 'invalid-user-credentials';
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $this->getUserRepository()->setNewPassword($user->id(), $passwordHash);

        return $this->authenticationService->withPassword($user->username(), $newPassword, true);
    }

    /**
     * @param $username
     * @return array|string
     */
    public function generateToken($username)
    {
        $user = $this->getUserRepository()->getByUsername($username);

        if (!$user instanceof User) {
            return 'user-not-found';
        }

        //  If the account has not been activated yet...
        if ($user->isActive() !== true) {
            // We just return the activation token
            return [
                'activation_token' => $user->activationToken(),
            ];
        }

        $token = openssl_random_pseudo_bytes(16, $strong);

        if ($strong !== true) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Unable to generate a strong token');
            // @codeCoverageIgnoreEnd
        }

        //  Use base62 for shorter tokens
        $token = BigInteger::factory('bcmath')->baseConvert(bin2hex($token), 16, 62);

        $expires = new DateTime("+" . self::TOKEN_TTL . " seconds");

        $tokenDetails = [
            'token'     => $token,
            'expiresIn' => self::TOKEN_TTL,
            'expiresAt' => $expires
        ];

        $this->getUserRepository()->addPasswordResetToken($user->id(), $tokenDetails);

        return $tokenDetails;
    }

    /**
     * @param $token
     * @param $newPassword
     * @return bool|string
     */
    public function updatePasswordUsingToken($token, $newPassword)
    {
        if (!$this->isPasswordValid($newPassword)) {
            return 'invalid-password';
        }

        //  Before attempting to update the password get the user record with the reset token
        $user = $this->getUserRepository()->getByResetToken($token);

        if (!$user instanceof User) {
            return 'invalid-token';
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $error = $this->getUserRepository()->updatePasswordUsingToken($token, $passwordHash);

        // If there's no error...
        // If the password updated correctly then reset the failed login attempt count for the user too
        if (is_null($error)) {
            $this->getUserRepository()->resetFailedLoginCounter($user->id());
            $user->resetFailedLoginAttempts();
            return null;
        }

        // Else return the error message
        return $error->message();
    }

    /**
     * @param AuthenticationService $authenticationService
     */
    public function setAuthenticationService(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }
}
