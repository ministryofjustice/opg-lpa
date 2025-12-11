<?php

namespace Application\Form\Validator;

use Application\Model\Service\Session\SessionUtility;
use Laminas\Http\Response;
use MakeShared\Logging\LoggerTrait;
use Laminas\Validator\Csrf as LaminasCsrfValidator;
use Psr\Log\LoggerAwareInterface;
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
 *
 * Psalm rightly objects to overriding final but we cannot fix this right now
 * @psalm-suppress InvalidExtendClass, MethodSignatureMismatch, ConstructorSignatureMismatch
 */
class Csrf extends LaminasCsrfValidator implements LoggerAwareInterface
{
    use LoggerTrait;

    public function __construct(protected $options = [], private ?SessionUtility $sessionUtility = null)
    {
        parent::__construct($options);
    }

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
     * @param mixed $value
     * @param mixed $context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        $hash = $this->getHash(true);

        if ($value !== $hash) {
            $this->getLogger()->error(sprintf(
                "Mismatched CSRF provided; expected %s received %s",
                $this->getHash(),
                $value,
            ));
            $this->getLogger()->error('Mismatched CSRF provided;', [
                'status' => Response::STATUS_CODE_500,
            ]);
            $this->error(self::NOT_SAME);
            return false;
        }

        return true;
    }


    public function getHash($regenerate = false)
    {
        $isNull = (null === $this->hash);

        if ($isNull || $regenerate) {
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
        $salt = $this->getSalt();

        if (empty($salt)) {
            throw new RuntimeException('CSRF salt cannot be null or empty');
        }

        $token = $this->sessionUtility->getFromMvc('CsrfValidator', 'token');

        if (!isset($token)) {
            $token = hash('sha512', openssl_random_pseudo_bytes(128));
            $this->sessionUtility->setInMvc('CsrfValidator', 'token', $token);
        }

        $this->hash = hash('sha512', $this->getName() . $token . $salt);
    }
}
