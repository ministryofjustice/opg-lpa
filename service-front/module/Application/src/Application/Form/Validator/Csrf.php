<?php
namespace Application\Form\Validator;

use Zend\Math\Rand;
use Zend\Session\Container as SessionContainer;
use Zend\Validator\Csrf as ZFCsrfValidator;

/**
 * A simplified replacement of Zend's Csrf Validator.
 *
 * This implementation is based on the idea that we have just a single secret token stored in the session
 * which does not change whilst the session is active.
 *
 * This means that session writes are not needed after the initial token is generated.
 *
 * This is to help mitigate the false positive Csrf validation errors we were getting.
 *
 * Class Csrf
 * @package Application\Form\Validator
 */
class Csrf extends ZFCsrfValidator {

    /**
     * Error messages
     * @var array
     */
    protected $messageTemplates = array(
        self::NOT_SAME => "The form submitted did not originate from the expected site",
    );

    /**
     * Does the provided token match the one generated?
     *
     * @param  string $value
     * @param  mixed $context
     * @return bool
     */
    public function isValid($value, $context = null){

        if( $value != $this->getHash() ){

            $this->error(self::NOT_SAME);
            return false;

        }

        return true;

    }

    /**
     * Generates a hash for the form.
     *
     * The hash is made up of:
     *  - The form's name
     *  - The CSRF token from the session.
     *  - The validator's salt.
     *
     * @return string
     */
    public function getHash(){

        $session = new SessionContainer('CsrfValidator');

        if( !isset($session->token) ){
            $session->token = hash( 'sha512', Rand::getBytes(128) );
        }

        //---

        return hash( 'sha512', $this->getName() . $session->token . $this->getSalt() );

    }

} // class
