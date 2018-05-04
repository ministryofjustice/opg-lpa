<?php

namespace Auth\Model\Service;

use DateTime;
use RuntimeException;
use Auth\Model\Service\DataAccess;
use Zend\Math\BigInteger\BigInteger;

class AuthenticationService extends AbstractService {

    //-------------

    /**
     * The maximum number of consecutive login attempts before an account is locked.
     */
    const MAX_ALLOWED_LOGIN_ATTEMPTS = 5;

    /**
     * The number of seconds before an auth token expires.
     */
    const TOKEN_TTL = 4500; // 75 minutes

    /**
     * The number of minutes to lock an account for after x failed login consecutive attempts.
     */
    const ACCOUNT_LOCK_TIME = 900; // 15 minutes

    //-------------

    public function withPassword( $username, $password, $createToken ){

        if( empty($username) || empty($password) ){
            return 'missing-credentials';
        }

        //---

        $user = $this->getUserDataSource()->getByUsername( $username );

        if( !( $user instanceof DataAccess\UserInterface ) ){
            return 'user-not-found';
        }

        //---

        if( !$user->isActive() ){
            return 'account-not-active';
        }

        //---

        if( $user->failedLoginAttempts() >= self::MAX_ALLOWED_LOGIN_ATTEMPTS ){

            // Unlock the account after 15 minutes
            if(
                ( $user->lastFailedLoginAttemptAt() instanceof DateTime ) &&
                ( $user->lastFailedLoginAttemptAt() > new DateTime( '-' . self::ACCOUNT_LOCK_TIME . " seconds" ) ) )
            {

                return 'account-locked/max-login-attempts';

            } else {

                // Reset field failed login counter
                $this->getUserDataSource()->resetFailedLoginCounter( $user->id() );
                $user->resetFailedLoginAttempts();

            }

        } // if

        //---

        // Check password
        if( !password_verify($password , $user->password()) ){

            $this->getUserDataSource()->incrementFailedLoginCounter( $user->id() );

            if( ( $user->failedLoginAttempts() + 1 ) >= self::MAX_ALLOWED_LOGIN_ATTEMPTS ){

                return 'invalid-user-credentials/account-locked';

            } else {

                return 'invalid-user-credentials';

            }

        }

        //---

        // ##### If we get here the user has been successfully authenticated.

        //  Before we do anything check to see if there are any inactivity flags set on the user
        //  If there are then set a boolean value to indicate that they will be cleared
        $inactivityFlagsCleared = !is_null($user->inactivityFlags());

        //---

        // Update the last logged-in time to now.
        $this->getUserDataSource()->updateLastLoginTime( $user->id() );

        //---

        // Ensure 'failed_login_attempts' is reset if needed
        if( $user->failedLoginAttempts() > 0 ){
            $this->getUserDataSource()->resetFailedLoginCounter( $user->id() );
        }

        //---

        $tokenDetails = array();

        if( $createToken ){

            $expires = new DateTime( "+".self::TOKEN_TTL." seconds" );

            //---

            do {

                $authToken = bin2hex(openssl_random_pseudo_bytes( 32, $strong ));

                // Use base62 for shorter tokens
                $authToken = BigInteger::factory('bcmath')->baseConvert( $authToken, 16, 62 );

                if( $strong !== true ){
                    // @codeCoverageIgnoreStart
                    throw new RuntimeException('Unable to generate a strong token');
                    // @codeCoverageIgnoreEnd
                }

                $created = (bool)$this->getUserDataSource()->setAuthToken(
                    $user->id(),
                    $expires,
                    $authToken
                );

            } while ( !$created );

            $tokenDetails = [
                'token' => $authToken,
                'expiresIn' => self::TOKEN_TTL,
                'expiresAt' => $expires
            ];

        } // if

        //---

        return [
            'userId' => $user->id(),
            'username' => $user->username(),
            'last_login' => $user->lastLoginAt(),
            'inactivityFlagsCleared' => $inactivityFlagsCleared,
        ] + $tokenDetails;

    } // function

    public function withToken( $token, $extendToken ){

        $user = $this->getUserDataSource()->getByAuthToken( $token );

        if( !( $user instanceof DataAccess\UserInterface ) ){
            return 'invalid-token';
        }

        //---

        $token = $user->authToken();

        if( !( $token instanceof DataAccess\TokenInterface ) ){
            return 'invalid-token';
        }

        //---

        if( $token->expiresAt() < (new DateTime()) ){
            return 'token-has-expired';
        }

        //---

        /**
         * This withToken() method is called many times per end-user request.
         * To reduce write load on the database we leave a few seconds grace period before re-extending the token.
         */
        $secondsSinceLastUpdate = time() - $token->updatedAt()->getTimestamp();

        if( $extendToken && $secondsSinceLastUpdate > 5 ){

            $expires = new DateTime( "+".self::TOKEN_TTL." seconds" );

            $this->getUserDataSource()->extendAuthToken( $user->id(), $expires );

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

        //---

        return [
            'token' => $token->id(),
            'userId' => $user->id(),
            'username' => $user->username(),
            'last_login' => $user->lastLoginAt(),
        ] + $expiresAt;

    } // function

    //-------------

    public function deleteToken( $token ){

        $this->getUserDataSource()->removeAuthToken( $token );

    }

} // class

