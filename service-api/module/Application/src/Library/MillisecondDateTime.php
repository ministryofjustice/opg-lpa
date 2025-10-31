<?php

namespace Application\Library;

use DateTimeZone;

/**
 * Adds support for including microseconds by default.
 *
 * Only works if $timezone is null or UTC.
 *
 * Class DateTime
 * @package Application\Library
 */
class MillisecondDateTime extends \DateTime
{
    public function __construct($time = 'now', DateTimeZone|null $timezone = null)
    {
        if ($time == 'now' && (is_null($timezone) || $timezone->getName() == 'UTC')) {
            $t = microtime(true);
            $micro = sprintf("%06d", ($t - floor($t)) * 1000000.0);
            $time = date('Y-m-d H:i:s.' . $micro, intval($t));
        }

        parent::__construct($time, $timezone);
    }

    /**
     * The built-in PHP DateTime seems to cause problems for psalm with PHP 8.2, giving a MethodSignatureMismatch
     * between its __unserialize() method and the one defined for DateTimeInterface.
     * @psalm-suppress MethodSignatureMismatch
     */
    public function __unserialize($data): void
    {
        parent::__unserialize($data);
    }
}
