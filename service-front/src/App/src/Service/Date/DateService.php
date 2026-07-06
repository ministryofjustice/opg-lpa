<?php

declare(strict_types=1);

namespace App\Service\Date;

use DateTime;

class DateService
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
