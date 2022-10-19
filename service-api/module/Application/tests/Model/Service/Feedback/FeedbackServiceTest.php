<?php

namespace ApplicationTest\Model\Service\Feedback;

use Application\Model\DataAccess\Repository\Feedback\FeedbackRepositoryInterface;
use Application\Model\Service\Feedback\Service as FeedbackService;
use ApplicationTest\Model\Service\Feedback\FeedbackServiceBuilder;
use ApplicationTest\Model\Service\AbstractServiceTest;
use DateTime;
use Mockery;

class FeedbackServiceTest extends AbstractServiceTest
{
    // feedback service under test
    private FeedbackService $sut;

    private FeedbackRepositoryInterface $feedbackRepository;

    public function setUp(): void
    {
        $this->feedbackRepository = Mockery::mock(FeedbackRepositoryInterface::class);

        $this->sut = (new FeedbackServiceBuilder())
            ->withFeedbackRepository($this->feedbackRepository)
            ->build();
    }

    public function testAddWithEmptyData()
    {
        $this->sut->getLogger()->shouldReceive('err');

        $this->assertFalse($this->sut->add([]));
    }

    public function testAddWithData()
    {
        $data = [
            'details' => 'feedback message'
        ];

        $this->feedbackRepository->shouldReceive('insert')->withArgs([$data])->andReturn(true);

        $this->assertTrue($this->sut->add($data));
    }

    public function testAddWithExtraData()
    {
        $data = [
            'details' => 'feedback message',
        ];

        $this->feedbackRepository->shouldReceive('insert')->withArgs([$data])->andReturn(true);

        // If we add an invalid field here, it will not be present in the 'shouldReceive' above.
        $data['invalid'] = true;

        $this->assertTrue($this->sut->add($data));
    }

    public function testGet()
    {
        $to = new DateTime();
        $from = new DateTime();
        $pruneDate = $this->sut->getPruneDate();

        // We should prune feedback received before the $pruneDate
        $this->feedbackRepository->shouldReceive('prune')->withArgs([$pruneDate]);

        // And receive a request for the feedback for the passed date range
        $this->feedbackRepository->shouldReceive('getForDateRange')->withArgs([$to, $from]);

        $this->assertInstanceOf('Traversable', $this->sut->get($to, $from));
    }
}
