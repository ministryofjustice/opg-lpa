<?php

namespace Application\Model\DataAccess\Mongo;

use DateTime;
use MongoDB\BSON\UTCDateTime;

/**
 * Class DateCallback
 * @package Application\Model\DataAccess\Mongo
 */
class DateCallback
{
    /**
     * @param DateTime $dateTime
     * @return UTCDateTime
     */
    public function __invoke(DateTime $dateTime)
    {
        return new UTCDateTime($dateTime);
    }
}
