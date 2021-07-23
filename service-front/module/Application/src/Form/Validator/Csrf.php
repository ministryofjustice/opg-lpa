<?php

namespace Application\Form\Validator;

use Application\Logging\LoggerTrait;
use Laminas\Math\Rand;
use Laminas\Session\Container;
use Laminas\Validator\Csrf as LaminasCsrfValidator;
use Redis;
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
 * Addendum 2021-07: It may be that the false positives are not caused by slow writes, but
 * by a race condition occurring between the main page and Ajax calls, both of
 * which trigger session writes. Analysis suggests that session writes triggered
 * by Ajax calls are overwriting CSRF tokens written into the session by the main
 * page load, resulting in incorrect regeneration of the CSRF token. The work-around
 * used here is to manage CSRF tokens in what is effectively a parallel session,
 * manually managed by a separate client injected into instances of this class.
 *
 * Class Csrf
 * @package Application\Form\Validator
 */
class Csrf extends LaminasCsrfValidator
{
    use LoggerTrait;

    // TODO docs
    private $csrfClient;

    /**
     * Error messages
     * @var array
     */
    protected $messageTemplates = [
        self::NOT_SAME => "Oops! Something went wrong with the information you entered. Please try again.",
    ];

    protected $hash = null;

    // TODO docs
    public function setCsrfClient($client)
    {
        $this->csrfClient = $client;
    }

    /**
     * Does the provided token match the one generated?
     *
     * @param  string $value
     * @param  mixed $context
     * @return bool
     */
    public function isValid($value, $context = null) : bool
    {
        $hash = $this->getHash();

        if ($value !== $hash) {
            $this->getLogger()->err(sprintf(
                "!!!!!!!!!!!!!!!!!!!!!!!! OH DEAR! Mismatched CSRF provided; expected %s BUT received %s",
                $hash,
                $value,
            ));

            $this->error(self::NOT_SAME);
            return false;
        }

        $this->getLogger()->debug(sprintf(
            "Got expected CSRF provided; expected %s and received %s",
            $hash,
            $value,
        ));

        return true;
    }

    public function getHash($regenerate = false) : string
    {
        if ($regenerate || is_null($this->hash)) {
            $this->hash = $this->generateHash();
        }

        return $this->hash;
    }

    public function setCsrfData($csrfData) : bool
    {
        $setOk = $this->csrfClient->set($csrfData);

        $this->getLogger()->debug(sprintf('++++++++++++++++++++++ TIME: %s; csrfData ATTEMPTED SET IN REDIS = %s; SUCCESSFUL? %s', microtime(), $csrfData, $setOk));

        return $setOk;
    }

    public function getCsrfData() : string
    {
        $csrfData = $this->csrfClient->get();

        $this->getLogger()->debug(sprintf('++++++++++++++++++++++ TIME: %s; csrfData FETCHED FROM REDIS = %s', microtime(), $csrfData));

        return $csrfData;
    }

    /**
     * Generate CSRF token
     *
     * The hash is made up of:
     *  - The form's name
     *  - The CSRF token from the session.
     *  - The validator's salt.
     *
     * @return string
     */
    protected function generateHash() : string
    {
        $salt = $this->getSalt();

        if ($salt == null || empty($salt)) {
            throw new RuntimeException('CSRF salt cannot be null or empty');
        }

        $this->getLogger()->debug(sprintf('##################### BEFORE CHECKING FOR csrf_token IN SESSION'));
        $token = $this->getCsrfData();

        if (empty($token)) {
            $this->getLogger()->debug('!!!!!!!!!!!!!!!!!!! NO csrfData IN SESSION; HERE\'s WHAT WE DO HAVE...');

            $token = hash('sha512', Rand::getBytes(128, true));
            $this->getLogger()->debug(sprintf('CSRF SESSION TOKEN RESET TO %s', $token));
            $this->setCsrfData($token);

            $this->getLogger()->debug('##################### AFTER CSRF TOKEN RESET:');
            $this->getCsrfData();
        }

        $hash = hash('sha512', $this->getName() . $token . $salt);

        $this->getLogger()->debug(sprintf(
            'DETERMINING CSRF HASH - FORM NAME: %s; TOKEN: %s; SALT: %s; HASH = %s',
            $this->getName(),
            $token,
            $salt,
            $hash
        ));

        return $hash;
    }
}