<?php

namespace Application\Model\DataAccess\Repository\Application;

use DateTime;
use MakeShared\DataModel\WhoAreYou\WhoAreYou;

interface WhoRepositoryInterface
{
    /**
     * Insert a new 'Who Are You' response.
     *
     * @param WhoAreYou $answer
     */
    public function insert(WhoAreYou $answer): void;

    /**
     * Return the WhoAreYou values for a specific date range.
     *
     * @param $start
     * @param $end
     * @param $options
     * @return array
     */
    public function getStatsForTimeRange(DateTime $start, DateTime $end, array $options): array;
}
