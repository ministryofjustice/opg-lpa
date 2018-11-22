<?php
namespace Application\Model\Service\Feedback;

use DateTime;
use Application\Model\DataAccess\Repository\Feedback\FeedbackRepositoryTrait;
use Application\Model\Service\AbstractService;

class Service extends AbstractService
{
    use FeedbackRepositoryTrait;

    const FEEDBACK_TTL = '-2 years';

    /**
     * @var DateTime
     */
    private $pruneBeforeDate = null;

    /**
     * Add a new item of feedback
     *
     * @param array $feedback
     * @return bool
     */
    public function add(array $feedback) : bool
    {
        // Filter out any 'non-allowed' fields
        $allowed = ['agent', 'fromPage', 'rating', 'details', 'email', 'phone'];
        $feedback = array_intersect_key($feedback, array_flip($allowed));

        // Feedback cannot be empty
        if (empty($feedback)) {
            return false;
        }

        return $this->getFeedbackRepository()->insert($feedback);
    }

    /**
     * Returns all feedback received between the given dates
     *
     * @param DateTime $from
     * @param DateTime $to
     * @return iterable
     */
    public function get(DateTime $from, DateTime $to) : iterable
    {
        // Prune old feedback
        $this->getFeedbackRepository()->prune($this->getPruneDate());

        // Return requested feedback
        return $this->getFeedbackRepository()->getForDateRange($from, $to);
    }

    /**
     * Returns the date before which all feedback should be deleted.
     * This value is cached for the life of the instance.
     *
     * @return DateTime
     */
    public function getPruneDate() {
        if (isset($this->pruneBeforeDate)) {
            return $this->pruneBeforeDate;
        }

        $this->pruneBeforeDate = new DateTime(self::FEEDBACK_TTL);

        return $this->pruneBeforeDate;
    }

}
