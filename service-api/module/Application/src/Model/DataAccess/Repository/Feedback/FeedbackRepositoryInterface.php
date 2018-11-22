<?php
namespace Application\Model\DataAccess\Repository\Feedback;

use DateTime;

interface FeedbackRepositoryInterface {

    /**
     * Insert a new feedback item
     *
     * @param array $feedback
     * @return bool
     */
    public function insert(array $feedback) : bool;

    /**
     * Return all feedback items for a given date range.
     *
     * @param DateTime $from
     * @param DateTime $to
     * @return mixed
     */
    public function getForDateRange(DateTime $from, DateTime $to) : iterable;

    /**
     * Delete all feedback received before teh passed date.
     *
     * @param DateTime $before
     * @return bool
     */
    public function prune(DateTime $before) : bool;

}
