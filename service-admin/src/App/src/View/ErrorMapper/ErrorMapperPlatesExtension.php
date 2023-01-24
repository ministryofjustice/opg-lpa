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
     * @return void
     */
    public function register(Engine $engine): void
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
     * @var array<string, array>
     */
    private $errors = [];

    /**
     * @param string[] $map
     * @param string $locale
     * @return void
     *
     * This is used as a template function, so psalm warning is bogus
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function addErrorMap(array $map, $locale = 'en-GB'): void
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
     *
     * This is used as a template function, so psalm warning is bogus
     * @psalm-suppress PossiblyUnusedMethod
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
     *
     * This is used as a template function, so psalm warning is bogus
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getFieldError($field, $slug, $locale = 'en-GB')
    {
        $slug = explode(':', $slug)[0];
        return ($this->errors[$locale][$field][$slug]['field']) ?? $slug;
    }
}
