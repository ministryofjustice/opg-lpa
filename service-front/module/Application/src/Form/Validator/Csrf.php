<?php

namespace Application\Form\Validator;

use MakeLogger\Logging\LoggerTrait;
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
    public function isValid($value, $context = null): bool
    {
        $hash = $this->getHash(true);

        $this->getLogger()->err(sprintf(
            "{isValid} Hash value (with regeneration set as true): %s",
            $hash
        ));

        if ($value !== $hash) {
            $this->getLogger()->err(sprintf(
                "Mismatched CSRF provided; expected %s received %s",
                $this->getHash(),
                $value,
            ));
            $this->error(self::NOT_SAME);
            return false;
        }

        return true;
    }


    public function getHash($regenerate = false)
    {
        $isNull = (null === $this->hash);

        $this->getLogger()->err(sprintf(
            "{getHash} Getting hash [regenerate: %s] [hash: %s] [isNull: %s]",
            $regenerate,
            $this->hash,
            $isNull
        ));


        if ( $isNull || $regenerate) {

            $this->getLogger()->err(sprintf(
                "{getHash} Getting hash - generating new version."
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
            "{generateHash} Generating hash"
        ));

        $salt = $this->getSalt();

        if ($salt == null || empty($salt)) {
            throw new RuntimeException('CSRF salt cannot be null or empty');
        }

        $session = new Container('CsrfValidator');

        if (!isset($session->token)) {
            $this->getLogger()->err(sprintf(
                "{generateHash} Generating hash - generating new token"
            ));
            $session->token = hash('sha512', Rand::getBytes(128, true));
        }

        $this->hash = hash('sha512', $this->getName() . $session->token . $salt);

        $this->getLogger()->err(sprintf(
            "{generateHash} Generated hash [salt: %s] [token: %s] [hash: %s] [name: %s]",
            $salt,
            $session->token,
            $this->hash,
            $this->getName()
        ));
    }
}
