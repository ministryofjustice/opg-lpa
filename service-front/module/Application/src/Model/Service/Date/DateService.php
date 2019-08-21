<?php

namespace Application\Model\Service\Date;

use DateTime;
use Exception;

class DateService implements IDateService
{

    /**
     * @return DateTime
     * @throws Exception
     */
    public function getNow()
    {
        return new DateTime('now');
    }

    /**
     * @return DateTime
     * @throws Exception
     */
    public function getToday()
    {
        return new DateTime('today');
    }
}
