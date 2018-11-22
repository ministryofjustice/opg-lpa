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
     * Return all feedback items for the given query.
     *
     * @param array $query
     * @return array
     */
    public function get(array $query);

    /**
     * Delete all feedback received before teh passed date.
     *
     * @param DateTime $before
     * @return bool
     */
    public function prune(DateTime $before) : bool;

}
