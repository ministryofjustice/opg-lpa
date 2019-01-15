<?php

namespace Application\Model\Service\Date;

use DateTime;

interface IDateService
{
    /**
     * @return DateTime
     */
    public function getNow();

    /**
     * @return DateTime
     */
    public function getToday();
}