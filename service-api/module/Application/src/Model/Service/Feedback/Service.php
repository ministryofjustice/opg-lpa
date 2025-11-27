<?php

namespace Application\Model\Service\Feedback;

use DateTime;
use Laminas\Http\Response;
use MakeShared\Logging\LoggerTrait;
use Traversable;
use Application\Model\DataAccess\Repository\Feedback\FeedbackRepositoryTrait;
use Application\Model\Service\AbstractService;

class Service extends AbstractService
{
    use FeedbackRepositoryTrait;
    use LoggerTrait;

    public const FEEDBACK_TTL = '-2 years';

    private FeedbackValidator $feedbackValidator;

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
    public function add(array $feedback): bool
    {
        // Filter out any 'non-allowed' fields
        $allowed = array_merge(
            FeedbackValidator::MANDATORY_FIELDS,
            FeedbackValidator::OPTIONAL_FIELDS
        );
        $feedback = array_intersect_key($feedback, array_flip($allowed));

        // Feedback cannot be empty
        if (empty($feedback)) {
            $this->getLogger()->error('Required fields for saving feedback not present', [
                'status' => Response::STATUS_CODE_500
            ]);
            return false;
        }

        // validator only checks the validity of fields which can be saved as feedback
        if (!$this->feedbackValidator->isValid($feedback)) {
            $this->getLogger()->error('Feedback data failed validation', [
                'status' => Response::STATUS_CODE_500
            ]);
            return false;
        }

        $dbInsertResult = $this->getFeedbackRepository()->insert($feedback);
        if (!$dbInsertResult) {
            $this->getLogger()->error('Error inserting feedback into database', [
                'status' => Response::STATUS_CODE_500
            ]);
        }

        return $dbInsertResult;
    }

    /**
     * Returns all feedback received between the given dates
     *
     * @param DateTime $from
     * @param DateTime $to
     * @return Traversable
     */
    public function get(DateTime $from, DateTime $to): Traversable
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
    public function getPruneDate()
    {
        if (isset($this->pruneBeforeDate)) {
            return $this->pruneBeforeDate;
        }

        $this->pruneBeforeDate = new DateTime(self::FEEDBACK_TTL);

        return $this->pruneBeforeDate;
    }

    /**
     * @param FeedbackValidator $feedbackValidator
     * @return void
     * @psalm-api
     */
    public function setFeedbackValidator(FeedbackValidator $feedbackValidator): void
    {
        $this->feedbackValidator = $feedbackValidator;
    }
}
