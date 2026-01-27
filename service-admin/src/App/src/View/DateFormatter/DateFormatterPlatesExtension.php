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

        if ($var instanceof DateTime) {
            return $var->format('jS M Y \\a\\t g:i:s a');
        }

        // If it's a string, try to parse it as a DateTime
        if (is_string($var)) {
            try {
                $date = new DateTime($var);
                return $date->format('jS M Y \\a\\t g:i:s a');
            } catch (\Exception $e) {
                return $default;
            }
        }

        return $default;
    }
}
