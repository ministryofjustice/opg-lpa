<?php
namespace App\View\DateFormatter;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use DateTime;


class DateFormatterPlatesExtension implements ExtensionInterface
{
    public function register(Engine $engine)
    {
        $engine->registerFunction('dateFormat', [$this, 'dateFormat']);
    }

    public function dateFormat($var)
    {
        return ($var instanceof DateTime ? $var->format('jS M Y \\a\\t g:i:s a') : $var);
    }
}
