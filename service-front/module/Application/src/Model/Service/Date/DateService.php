<?php

namespace Application\Model\Service\Date;

use DateTime;

class DateService implements IDateService
{
    public function getNow(): DateTime
    {
        return new DateTime('now');
    }

    public function getToday(): DateTime
    {
        return new DateTime('today');
    }
}
