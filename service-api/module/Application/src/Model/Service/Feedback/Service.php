<?php
namespace Application\Model\Service\Feedback;

use Application\Model\DataAccess\Repository\Feedback\FeedbackRepositoryTrait;
use Application\Model\Service\AbstractService;

class Service extends AbstractService
{
    use FeedbackRepositoryTrait;

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

    public function get(string $from, string $to) : array
    {
        die(__METHOD__.' not done yet');
    }

}
