<?php

namespace Application\Model\Service\Authentication;

use Application\Model\DataAccess\Repository\User\UserRepositoryTrait;
use Application\Model\DataAccess\Repository\User\TokenInterface as Token;
use Application\Model\DataAccess\Repository\User\UserInterface as User;
use Application\Model\Service\AbstractService;
use Zend\Math\BigInteger\BigInteger;
use DateTime;

class Service extends AbstractService
{
    use UserRepositoryTrait;

    /**
     * The maximum number of consecutive login attempts before an account is locked.
     */
    const MAX_ALLOWED_LOGIN_ATTEMPTS = 5;

    /**
     * The number of seconds before an auth token expires.
     */
     // const TOKEN_TTL = 4500; // 75 minutes
     const TOKEN_TTL = 600; // 10 minutes  for testing the session time out pop up window bug, TO REMOVE AFTER TESTING

    /**
     * The number of minutes to lock an account for after x failed login consecutive attempts.
     */
    const ACCOUNT_LOCK_TIME = 900; // 15 minutes

    public function withPassword($username, $password, $createToken)
    {
        if (empty($username) || empty($password)) {
            return 'missing-credentials';
        }

        $user = $this->getUserRepository()->getByUsername($username);

        if (!$user instanceof User) {
            return 'user-not-found';
        }

        if (!$user->isActive()) {
            return 'account-not-active';
        }

        if ($user->failedLoginAttempts() >= self::MAX_ALLOWED_LOGIN_ATTEMPTS) {
            // Unlock the account after 15 minutes
            if ($user->lastFailedLoginAttemptAt() instanceof DateTime
                && $user->lastFailedLoginAttemptAt() > new DateTime('-' . self::ACCOUNT_LOCK_TIME . " seconds")) {

                return 'account-locked/max-login-attempts';
            } else {
                // Reset field failed login counter
                $this->getUserRepository()->resetFailedLoginCounter($user->id());
                $user->resetFailedLoginAttempts();
            }
        }

        // Check password
        if (!password_verify($password, $user->password())) {
            $this->getUserRepository()->incrementFailedLoginCounter($user->id());

            if (($user->failedLoginAttempts() + 1) >= self::MAX_ALLOWED_LOGIN_ATTEMPTS) {
                return 'invalid-user-credentials/account-locked';
            } else {
                return 'invalid-user-credentials';
            }
        }

        // ##### If we get here the user has been successfully authenticated.

        //  Before we do anything check to see if there are any inactivity flags set on the user
        //  If there are then set a boolean value to indicate that they will be cleared
        $inactivityFlagsCleared = !is_null($user->inactivityFlags());

        // Update the last logged-in time to now.
        $this->getUserRepository()->updateLastLoginTime($user->id());

        // Ensure 'failed_login_attempts' is reset if needed
        if ($user->failedLoginAttempts() > 0) {
            $this->getUserRepository()->resetFailedLoginCounter($user->id());
        }

        $tokenDetails = array();

        if ($createToken) {
            $expires = new DateTime("+" . self::TOKEN_TTL . " seconds");

            do {
                $authToken = bin2hex(random_bytes(32));

                // Use base62 for shorter tokens
                $authToken = BigInteger::factory('bcmath')->baseConvert($authToken, 16, 62);

                $created = (bool)$this->getUserRepository()->setAuthToken(
                    $user->id(),
                    $expires,
                    $authToken
                );
            } while (!$created);

            $tokenDetails = [
                'token' => $authToken,
                'expiresIn' => self::TOKEN_TTL,
                'expiresAt' => $expires
            ];
        }

        return [
                'userId' => $user->id(),
                'username' => $user->username(),
                'last_login' => $user->lastLoginAt(),
                'inactivityFlagsCleared' => $inactivityFlagsCleared,
            ] + $tokenDetails;
    }

    public function withToken($token, $extendToken)
    {
        $user = $this->getUserRepository()->getByAuthToken($token);

        if (!$user instanceof User) {
            return 'invalid-token';
        }

        $token = $user->authToken();

        if (!($token instanceof Token)) {
            return 'invalid-token';
        }

        if ($token->expiresAt() < (new DateTime())) {
            return 'token-has-expired';
        }

        /**
         * This withToken() method is called many times per end-user request.
         * To reduce write load on the database we leave a few seconds grace period before re-extending the token.
         */
        $secondsSinceLastUpdate = time() - $token->updatedAt()->getTimestamp();

        if ($extendToken && $secondsSinceLastUpdate > 5) {
            $expires = new DateTime("+" . self::TOKEN_TTL . " seconds");

            $this->getUserRepository()->extendAuthToken($user->id(), $expires);

            $expiresAt = [
                'expiresIn' => self::TOKEN_TTL,
                'expiresAt' => $expires
            ];
        } else {
            // Otherwise return the existing details.
            $expiresAt = [
                'expiresIn' => (int)abs(time() - $token->expiresAt()->getTimestamp()),
                'expiresAt' => $token->expiresAt()
            ];
        }

        return [
                'token' => $token->id(),
                'userId' => $user->id(),
                'username' => $user->username(),
                'last_login' => $user->lastLoginAt(),
            ] + $expiresAt;
    }
}
