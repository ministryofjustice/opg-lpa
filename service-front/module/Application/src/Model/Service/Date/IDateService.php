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

    /**
     * @return DateTime
     */
    public function getUtcNow();

    /**
     * @return DateTime
     */
    public function getUtcToday();
}