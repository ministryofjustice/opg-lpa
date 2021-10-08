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
    public function register(Engine $engine)
    {
        $engine->registerFunction('dateFormat', [$this, 'dateFormat']);
    }

    /**
     * @param mixed $var Value to format as a date
     * @param mixed $default Default value to return if $var is not a DateTime;
     * defaults to $var itself
     */
    public function dateFormat($var, $default = null)
    {
        if (is_null($default)) {
            $default = $var;
        }

        return ($var instanceof DateTime ? $var->format('jS M Y \\a\\t g:i:s a') : $default);
    }
}
