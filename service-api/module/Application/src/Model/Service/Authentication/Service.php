<?php

namespace Application\Model\Service\Authentication;

use Application\Model\DataAccess\Repository\User\UserRepositoryTrait;
use Application\Model\DataAccess\Repository\User\TokenInterface as Token;
use Application\Model\DataAccess\Repository\User\UserInterface as User;
use Application\Model\Service\AbstractService;
use DateTime;

class Service extends AbstractService
{
    use UserRepositoryTrait;

    /**
     * The maximum number of consecutive login attempts before an account is locked.
     */
    public const MAX_ALLOWED_LOGIN_ATTEMPTS = 5;

    /**
     * Default number of seconds before an auth token expires.
     */
    public const TOKEN_TTL = 4500; // 75 minutes

    /**
     * The actual number of seconds before an auth token expires.
     */
    private $tokenTtl;

    /**
     * The number of minutes to lock an account for after x failed login consecutive attempts.
     */
    public const ACCOUNT_LOCK_TIME = 900; // 15 minutes

    public function __construct($tokenTtl = self::TOKEN_TTL)
    {
        $this->tokenTtl = $tokenTtl;
    }

    public function withPassword(#[\SensitiveParameter] ?string $username, #[\SensitiveParameter] ?string $password, bool $createToken): array|string
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
            if (
                $user->lastFailedLoginAttemptAt() instanceof DateTime
                && $user->lastFailedLoginAttemptAt() > new DateTime('-' . self::ACCOUNT_LOCK_TIME . " seconds")
            ) {
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
            $expires = new DateTime("+" . $this->tokenTtl . " seconds");

            do {
                $authToken = sprintf("0x%s", bin2hex(random_bytes(32)));

                // Use base62 for shorter tokens
                $authToken = gmp_strval($authToken, 62);

                $created = $this->getUserRepository()->setAuthToken(
                    $user->id(),
                    $expires,
                    $authToken
                );
            } while (!$created);

            $tokenDetails = [
                'token' => $authToken,
                'expiresIn' => $this->tokenTtl,
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

    public function withToken($tokenStr, $extendToken)
    {
        // limit token updates to once every 5 seconds
        $throttle = true;

        // will be derived from the tokenTtl if we decide to update
        // (i.e. not ignored because of throttling)
        $expiresAt = null;

        return $this->updateToken($tokenStr, $extendToken, $throttle, $expiresAt);
    }

    /**
     * $tokenStr: string; representation of token, derived from request
     * $needsUpdate: bool; set to true to decide whether to try to update the
     *     token expiry; if false, no update is attempted
     * $throttle: bool; if $needsUpdate is true and $throttle is true,
     *     the update will still only be applied if the last update time for the
     *     token is more than 5 seconds ago
     * $expiresAt: DateTime|null; if null, defaults to the current time +
     *     the tokenTtl on this service
     */
    public function updateToken($tokenStr, $needsUpdate = true, $throttle = true, $expiresAt = null)
    {
        $user = $this->getUserRepository()->getByAuthToken($tokenStr);

        if (!($user instanceof User)) {
            return 'invalid-token';
        }

        $token = $user->authToken();

        if (!($token instanceof Token)) {
            return 'invalid-token';
        }

        $currentDatetime = new DateTime();

        if ($token->expiresAt() < $currentDatetime) {
            return 'token-has-expired';
        }

        $currentTimestamp = $currentDatetime->getTimestamp();

        // the maximum expiry datetime is set via the TTL on this service
        $maxExpiresAt = $currentDatetime->modify("+" . $this->tokenTtl . " seconds");

        /**
         * If $throttle, only need to update if not updated in last 5 seconds.
         * This withToken() method is called many times per end-user request.
         * To reduce write load on the database we leave a few seconds grace
         * period before re-extending the token.
         */
        if ($throttle) {
            $secondsSinceLastUpdate = $currentTimestamp - $token->updatedAt()->getTimestamp();
            if ($secondsSinceLastUpdate < 5) {
                $needsUpdate = false;
            }
        }

        // ensure expiresAt is set to some value
        if ($expiresAt === null) {
            // if we need to update, use the default expiry
            if ($needsUpdate) {
                $expiresAt = $maxExpiresAt;
            } else {
                // not updating, just get the token's current expiresAt
                $expiresAt = $token->expiresAt();
            }
        } else {
            // limit how far ahead expiry is set to <= the TTL for this service
            $expiresAt = min($maxExpiresAt, $expiresAt);
        }

        // derive expiresIn
        $expiresIn = (int)abs($currentTimestamp - $expiresAt->getTimestamp());

        // apply the update if required
        if ($needsUpdate) {
            $result = $this->getUserRepository()->updateAuthTokenExpiry($user->id(), $expiresAt);

            if (!$result) {
                return 'token-update-not-applied';
            }
        }

        return [
            'token' => $token->id(),
            'userId' => $user->id(),
            'username' => $user->username(),
            'last_login' => $user->lastLoginAt(),
            'expiresIn' => $expiresIn,
            'expiresAt' => $expiresAt,
        ];
    }
}
