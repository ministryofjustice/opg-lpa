<?php

namespace Application\Form\Validator;

use Application\Logging\LoggerTrait;
use Laminas\Math\Rand;
use Laminas\Session\Container;
use Laminas\Validator\Csrf as LaminasCsrfValidator;
use RuntimeException;

/**
 * A simplified replacement of Zend's Csrf Validator.
 *
 * This implementation is based on the idea that we have just a single secret token stored in the session
 * which does not change whilst the session is active.
 *
 * This means that session writes are not needed after the initial token is generated.
 *
 * This is to help mitigate the false positive Csrf validation errors we were getting,
 * which is caused by slow writes of the session data.
 *
 * Class Csrf
 * @package Application\Form\Validator
 */
class Csrf extends LaminasCsrfValidator
{
    use LoggerTrait;

    /**
     * Error messages
     * @var array
     */
    protected $messageTemplates = [
        self::NOT_SAME => "Oops! Something went wrong with the information you entered. Please try again.",
    ];

    /**
     * Does the provided token match the one generated?
     *
     * @param  string $value
     * @param  mixed $context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        $this->getLogger()->err(sprintf(
            "{CSRF:isValid} [hash: %s]",
            substr($this->hash, 0, 8)
        ));

        $hash = $this->getHash();

        $this->getLogger()->err(sprintf(
            "{CSRF:isValid} [hash: %s]",
            substr($hash, 0, 8)
        ));

        if ($value !== $hash) {
            $callStack = json_encode(debug_backtrace() );
            $this->getLogger()->err(sprintf(
                "{CSRF:isValidERROR} Mismatched CSRF provided; [value: %s] [hash: %s] [context: %s]",
                substr($value, 0, 8),
                substr($hash, 0, 8),
                json_encode($context)
            ));
            $this->getLogger()->err($callStack);
            $this->error(self::NOT_SAME);
            return false;
        }

        return true;
    }


    public function getHash($regenerate = false)
    {
        $isNull = (null === $this->hash);
        $callStack = json_encode(debug_backtrace() );

        $this->getLogger()->err(sprintf(
            "{CSRF:getHash} Getting hash [regenerate: %s] [isNull: %s] [hash: %s]",
            $regenerate,
            $isNull,
            substr($this->hash, 0, 8)
        ));
        $this->getLogger()->err($callStack);

        if ( $isNull || $regenerate) {

            $this->getLogger()->err(sprintf(
                "{CSRF:getHash} Generating new hash"
            ));

            $this->generateHash();
        }
        return $this->hash;
    }


    /**
     * Generate CSRF token
     *
     * The hash is made up of:
     *  - The form's name
     *  - The CSRF token from the session.
     *  - The validator's salt.
     *
     * @return void
     */
    protected function generateHash()
    {
        $this->getLogger()->err(sprintf(
            "{CSRF:generateHash} Generating hash"
        ));

        $salt = $this->getSalt();

        if ($salt == null || empty($salt)) {
            throw new RuntimeException('CSRF salt cannot be null or empty');
        }

        $session = new Container('CsrfValidator');

        if (!isset($session->token)) {
            $this->getLogger()->err(sprintf(
                "{CSRF:generateHash} Generating new token "
            ));
            $session->token = hash('sha512', Rand::getBytes(128, true));
        }

        $this->hash = hash('sha512', $this->getName() . $session->token . $salt);

        $this->getLogger()->err(sprintf(
            "{CSRF:generateHash} Generated hash [salt: %s] [token: %s] [hash: %s]",
            $salt,
            substr($session->token, 0, 8),
            substr($this->hash, 0, 8)
        ));
    }



}
