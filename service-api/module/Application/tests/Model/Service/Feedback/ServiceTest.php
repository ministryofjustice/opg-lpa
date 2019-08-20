<?php
namespace ApplicationTest\Model\Service\Feedback;

use Application\Model\DataAccess\Repository\Feedback\FeedbackRepositoryInterface;
use ApplicationTest\Model\Service\AbstractServiceTest;
use DateTime;
use Mockery;

class ServiceTest extends AbstractServiceTest
{

    public function testAddWithEmptyData()
    {
        $feedbackRepository = Mockery::mock(FeedbackRepositoryInterface::class);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withFeedbackRepository($feedbackRepository)
            ->build();

        $result = $service->add([]);

        $this->assertFalse($result);
    }

    public function testAddWithDate()
    {
        $data = [
            'details' => 'feedback message'
        ];

        $feedbackRepository = Mockery::mock(FeedbackRepositoryInterface::class);

        $feedbackRepository->shouldReceive('insert')->withArgs([$data])->andReturn(true);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withFeedbackRepository($feedbackRepository)
            ->build();

        $result = $service->add($data);

        $this->assertTrue($result);
    }

    public function testAddWithExtraDate()
    {
        $data = [
            'details' => 'feedback message',
        ];

        $feedbackRepository = Mockery::mock(FeedbackRepositoryInterface::class);

        $feedbackRepository->shouldReceive('insert')->withArgs([$data])->andReturn(true);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withFeedbackRepository($feedbackRepository)
            ->build();

        // If we add an invalid field here, it should not be present in the 'shouldReceive' above.
        $data['invalid'] = true;

        $result = $service->add($data);

        $this->assertTrue($result);
    }

    public function testGet()
    {
        $feedbackRepository = Mockery::mock(FeedbackRepositoryInterface::class);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withFeedbackRepository($feedbackRepository)
            ->build();

        $to = new DateTime;
        $from = new DateTime;
        $pruneDate = $service->getPruneDate();

        // We should prune feedback received before the $pruneDate
        $feedbackRepository->shouldReceive('prune')->withArgs([$pruneDate]);

        // And receive a request for the feedback for the passed date range
        $feedbackRepository->shouldReceive('getForDateRange')->withArgs([$to, $from]);

        $service->get($to, $from);
    }

}
