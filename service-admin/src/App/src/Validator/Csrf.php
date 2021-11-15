<?php

namespace App\Validator;

use Laminas\Validator\Csrf as ZendCsrf;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Simplified CSRF validator that relies on a passed secret
 * Where the secret comes from is beyond the scope of this class
 *
 * @package App\Validator
 */
class Csrf extends ZendCsrf
{
    /**
     * Set to null in order to force the user to manually set it
     *
     * @var null|string
     */
    protected $name = null;

    /**
     * @var array<string, string>
     */
    protected $messageTemplates = [
        self::NOT_SAME => 'csrf',
    ];

    /**
     * Csrf constructor
     *
     * @param array<string, mixed> $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (!isset($options['secret']) || strlen($options['secret']) < 64) {
            throw new InvalidArgumentException('A (64 character) CSRF secret is required');
        }

        $this->hash = $options['secret'];
    }

    /**
     * @param string $value
     * @param null $context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        if ($value !== $this->getHash()) {
            $this->error(self::NOT_SAME);
            return false;
        }

        return true;
    }

    /**
     * @param bool $regenerate
     * @return string
     */
    public function getHash($regenerate = false)
    {
        $name = $this->getName();

        if (!is_string($name) || strlen($name) == 0) {
            throw new UnexpectedValueException('CSRF name needs to be set');
        }

        return hash('sha512', $this->hash . $name);
    }
}
