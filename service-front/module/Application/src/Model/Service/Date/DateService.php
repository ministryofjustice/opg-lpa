<?php

namespace Application\Model\Service\Date;

use DateTime;
use DateTimeZone;

class DateService implements IDateService
{

    /**
     * @return DateTime
     */
    public function getNow()
    {
        return new DateTime('now');
    }

    /**
     * @return DateTime
     */
    public function getToday()
    {
        return new DateTime('today');
    }

    /**
     * @return DateTime
     */
    public function getUtcNow()
    {
        return new DateTime('now', DateTimeZone::UTC);
    }

    /**
     * @return DateTime
     */
    public function getUtcToday()
    {
        return new DateTime('today', DateTimeZone::UTC);
    }
}