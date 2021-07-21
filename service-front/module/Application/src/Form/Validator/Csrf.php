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

    protected $hash = null;

    /**
     * Does the provided token match the one generated?
     *
     * @param  string $value
     * @param  mixed $context
     * @return bool
     */
    public function isValid($value, $context = null)
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


    public function getHash($regenerate = false)
    {
        if ($regenerate || is_null($this->hash)) {
            $this->hash = $this->generateHash();
        }

        return $this->hash;
    }

    public function showSessionData()
    {
        $key = 'PHPREDIS_SESSION:' . $_COOKIE['lpa2'];

        $redis = new Redis();
        $redis->connect('redis');
        $sessionData = $redis->get($key);
        $stamp = time();
        $redis->close();

        $this->getLogger()->debug(sprintf('++++++++++++++++++++++ TIME: %s; DATA STORED IN REDIS FOR SESSION = %s', $stamp, $sessionData));
        //$this->getLogger()->debug(sprintf('++++++++++++++++++++++ CONTENTS OF $_SESSION: %s', print_r($_SESSION, true)));

        return $sessionData;
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
    protected function generateHashUsingSession()
    {
        $salt = $this->getSalt();

        if ($salt == null || empty($salt)) {
            throw new RuntimeException('CSRF salt cannot be null or empty');
        }

        $this->getLogger()->debug(sprintf('##################### BEFORE CHECKING FOR csrf_token IN SESSION'));
        $this->showSessionData();

        $session = new Container('CsrfValidator');

        if (isset($session->csrf_token)) {
            $token = $session->csrf_token;
        }
        else {
            $this->getLogger()->debug('!!!!!!!!!!!!!!!!!!! NO csrf_token IN SESSION; HERE\'s WHAT WE DO HAVE...');

            $token = hash('sha512', Rand::getBytes(128, true));
            $this->getLogger()->debug(sprintf('CSRF SESSION TOKEN WAS RESET: %s', $token));
            $session->csrf_token = $token;
            session_write_close();

            $this->getLogger()->debug('##################### AFTER RESETTING CSRF TOKEN:');
            $this->showSessionData();
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

    public function setCsrfData($csrfData)
    {
        $key = 'CSRF-' . $_COOKIE['lpa2'];

        $redis = new Redis();
        $redis->connect('redis');
        $setOk = $redis->set($key, sprintf('%s', $csrfData));
        $stamp = time();
        $redis->close();

        $this->getLogger()->debug(sprintf('++++++++++++++++++++++ TIME: %s; KEY: %s; csrfData ATTEMPTED SET IN REDIS = %s; SUCCESSFUL? %s', $stamp, $key, $csrfData, $setOk));
    }

    public function getCsrfData()
    {
        $key = 'CSRF-' . $_COOKIE['lpa2'];

        $redis = new Redis();
        $redis->connect('redis');
        $csrfData = $redis->get($key);
        $stamp = time();
        $redis->close();

        $this->getLogger()->debug(sprintf('++++++++++++++++++++++ TIME: %s; KEY: %s; csrfData FETCHED FROM REDIS = %s', $stamp, $key, $csrfData));

        return $csrfData;
    }

    protected function generateHash()
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