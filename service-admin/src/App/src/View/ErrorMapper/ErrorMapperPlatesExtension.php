<?php

namespace App\View\ErrorMapper;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use Laminas\Stdlib\ArrayUtils;

/**
 * Plates Extension providing a View Helper for the ErrorMapper service.
 *
 * Class ErrorMapperPlatesExtension
 * @package App\View\ErrorMapper
 */
class ErrorMapperPlatesExtension implements ExtensionInterface
{
    /**
     * @param Engine $engine
     */
    public function register(Engine $engine)
    {
        /** @phpstan-ignore-next-line */
        $engine->registerFunction('addErrorMap', [$this, 'addErrorMap']);

        /** @phpstan-ignore-next-line */
        $engine->registerFunction('summaryError', [$this, 'getSummaryError']);

        /** @phpstan-ignore-next-line */
        $engine->registerFunction('fieldError', [$this, 'getFieldError']);
    }

    /**
     * Store of error messages.
     * @var array
     */
    private $errors = [];

    /**
     * @param array $map
     * @param string $locale
     */
    public function addErrorMap(array $map, $locale = 'en-GB')
    {
        // Ensure there's an array for the locale
        if (!isset($this->errors[$locale])) {
            $this->errors[$locale] = [];
        }

        $this->errors[$locale] = ArrayUtils::merge($this->errors[$locale], $map);
    }

    /**
     * @param string $field
     * @param string $slug
     * @param string $locale
     * @return mixed
     */
    public function getSummaryError($field, $slug, $locale = 'en-GB')
    {
        $slug = explode(':', $slug)[0];
        return ($this->errors[$locale][$field][$slug]['summary']) ?? $slug;
    }

    /**
     * @param string $field
     * @param string $slug
     * @param string $locale
     * @return mixed
     */
    public function getFieldError($field, $slug, $locale = 'en-GB')
    {
        $slug = explode(':', $slug)[0];
        return ($this->errors[$locale][$field][$slug]['field']) ?? $slug;
    }
}
