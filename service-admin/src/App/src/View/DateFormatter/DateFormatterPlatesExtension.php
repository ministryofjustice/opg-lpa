<?php

namespace App\View\DateFormatter;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use DateTime;

/**
 * Plates extension which provides the dateFormat template filter.
 */
class DateFormatterPlatesExtension implements ExtensionInterface
{
    /**
     * @param Engine $engine
     * @return void
     */
    public function register(Engine $engine): void
    {
        /** @phpstan-ignore-next-line */
        $engine->registerFunction('dateFormat', [$this, 'dateFormat']);
    }

    /**
     * @param mixed $var Value to format as a date
     * @param mixed $default Default value to return if $var is not a DateTime;
     * defaults to $var itself
     * @return string|null
     *
     * This is used as a template function, so psalm warning is bogus
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function dateFormat($var, $default = null)
    {
        if (is_null($default)) {
            $default = $var;
        }

        return ($var instanceof DateTime ? $var->format('jS M Y \\a\\t g:i:s a') : $default);
    }
}
