<?php

namespace Application\Model\Service;

use DateTime;
use Zend\Validator\EmailAddress as EmailAddressValidator;
use Zend\Math\BigInteger\BigInteger;

class RegistrationService extends AbstractService {

    use PasswordValidatorTrait;
    
    //-------------

    public function create( $username, $password ){

        //-----------------------------------------------
        // Validate details

        //------
        // Check username is a valid. i.e. an email address...

        $emailValidator = new EmailAddressValidator();

        if ( !$emailValidator->isValid($username) ) {
            return 'invalid-username';
        }

        //------
        // Check the username isn't already used...

        $user = $this->getUserDataSource()->getByUsername( $username );

        if( !is_null( $user ) ){
            return 'username-already-exists';
        }

        if ( !$this->isPasswordValid( $password ) ) {
            return 'invalid-password';
        }
        
        // If we get here, all details are valid.

        //-----------------------------------------------
        // Create the account

        /**
         * We use a loop here to ensure we retry to create the account if there's
         * a clash with the userId or activation_token (despite this being extremely unlikely).
         */
        do {

            // Create a 32 character user id and activation token.

            $userId = bin2hex(openssl_random_pseudo_bytes( 16 ));
            $activationToken = bin2hex(openssl_random_pseudo_bytes( 16 ));

            // Use base62 for shorter tokens
            $activationToken = BigInteger::factory('bcmath')->baseConvert( $activationToken, 16, 62 );

            $created = (bool)$this->getUserDataSource()->create( $userId,[
                'identity' => $username,
                'active' => false,
                'activation_token' => $activationToken,
                'password_hash' => password_hash( $password, PASSWORD_DEFAULT ),
                'created' => new DateTime(),
                'last_updated' => new DateTime(),
                'failed_login_attempts' => 0,
            ]);

        } while ( !$created );

        //---

        return [
            'userId' => $userId,
            'activation_token' => $activationToken,
        ];

    } // function

    public function activate( $token ){

        $result = $this->getUserDataSource()->activate( $token );

        if( is_null($result) || $result === false ){
            return 'account-not-found';
        }

        return true;

    } // function

} // class
