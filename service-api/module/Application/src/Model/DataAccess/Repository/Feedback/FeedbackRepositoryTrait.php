<?php
namespace Application\Model\DataAccess\Repository\Feedback;

trait FeedbackRepositoryTrait {

    /**
     * @var FeedbackRepositoryInterface
     */
    private $feedbackRepository;

    /**
     * @param FeedbackRepositoryInterface $repo
     */
    public function setFeedbackRepository(FeedbackRepositoryInterface $repo)
    {
        $this->feedbackRepository = $repo;
    }

    /**
     * @return FeedbackRepositoryInterface
     */
    private function getFeedbackRepository() : FeedbackRepositoryInterface
    {
        if (!($this->feedbackRepository instanceof FeedbackRepositoryInterface)) {
            throw new \RuntimeException("Instance of FeedbackRepository not set");
        }

        return $this->feedbackRepository;
    }

}
