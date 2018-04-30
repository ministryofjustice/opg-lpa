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
class DateTime extends \DateTime {

    public function __construct ($time='now', DateTimeZone $timezone=null) {

        if( $time == 'now' && (is_null($timezone) || $timezone->getName() == 'UTC') ){
            $t = microtime(true);
            $micro = sprintf("%06d",($t - floor($t)) * 1000000);
            $time = date('Y-m-d H:i:s.'.$micro,$t);
        }

        parent::__construct( $time, $timezone );

    } // function

} // class
