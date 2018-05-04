<?php

namespace Application\Model\Service;

use Application\Model\Service\DataAccess;
use Zend\Math\BigInteger\BigInteger;
use DateTime;
use RuntimeException;

class PasswordResetService extends AbstractService
{
    const TOKEN_TTL = 86400; // 24 hours

    use PasswordValidatorTrait;

    public function generateToken($username)
    {
        $dataSource = $this->getUserDataSource();

        $user = $dataSource->getByUsername($username);

        if (!$user instanceof DataAccess\UserInterface) {
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

        $dataSource->addPasswordResetToken( $user->id(), $tokenDetails );

        return $tokenDetails;
    }

    public function updatePasswordUsingToken($token, $newPassword)
    {
        if (!$this->isPasswordValid($newPassword)) {
            return 'invalid-password';
        }

        //  Before attempting to update the password get the user record with the reset token
        $user = $this->getUserDataSource()->getByResetToken($token);

        if (!$user instanceof DataAccess\UserInterface) {
            return 'invalid-token';
        }

        $passwordHash = password_hash( $newPassword, PASSWORD_DEFAULT );

        $result = $this->getUserDataSource()->updatePasswordUsingToken($token, $passwordHash);

        //  If the password updated correctly then reset the failed login attempt count for the user too
        if ($result === true) {
            $this->getUserDataSource()->resetFailedLoginCounter($user->id());
            $user->resetFailedLoginAttempts();
        }

        return $result;
    }
}
