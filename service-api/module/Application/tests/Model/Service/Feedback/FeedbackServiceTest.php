<?php

namespace ApplicationTest\Model\Service\Feedback;

use Application\Model\DataAccess\Repository\Feedback\FeedbackRepositoryInterface;
use Application\Model\Service\Feedback\FeedbackValidator;
use Application\Model\Service\Feedback\Service as FeedbackService;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use DateTime;
use Mockery;
use Mockery\MockInterface;

class FeedbackServiceTest extends AbstractServiceTestCase
{
    // feedback service under test
    private FeedbackService $sut;

    private MockInterface&FeedbackRepositoryInterface $feedbackRepository;

    private MockInterface&FeedbackValidator $feedbackValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->feedbackRepository = Mockery::mock(FeedbackRepositoryInterface::class);
        $this->feedbackValidator = Mockery::mock(FeedbackValidator::class);

        $this->sut = new FeedbackService();
        $this->sut->setFeedbackRepository($this->feedbackRepository);
        $this->sut->setFeedbackValidator($this->feedbackValidator);
        $this->sut->setLogger($this->logger);
    }

    public function testAddWithEmptyData()
    {
        $this->logger->shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $extra) {
                $this->assertSame('Required fields for saving feedback not present', $message);
                $this->assertSame('FEEDBACK_MISSING_REQUIRED_FIELDS', $extra['error_code']);
                $this->assertSame(500, $extra['status']);

                return true;
            });

        $this->assertFalse($this->sut->add([]));
    }

    public function testAddWithData()
    {
        $data = [
            'details' => 'feedback message'
        ];

        $this->feedbackValidator->shouldReceive('isValid')->with($data)->andReturn(true);

        $this->feedbackRepository->shouldReceive('insert')->withArgs([$data])->andReturn(true);

        $this->assertTrue($this->sut->add($data));
    }

    public function testAddWithExtraData()
    {
        $data = [
            'details' => 'feedback message',
        ];

        $this->feedbackValidator->shouldReceive('isValid')->with($data)->andReturn(true);

        $this->feedbackRepository->shouldReceive('insert')->withArgs([$data])->andReturn(true);

        // If we add an invalid field here, it will not be present in the 'shouldReceive' above.
        $data['invalid'] = true;

        $this->assertTrue($this->sut->add($data));
    }

    public function testAddValidationFails()
    {
        $data = ['details' => 'looks great'];

        $this->feedbackValidator->shouldReceive('isValid')->with($data)->andReturn(false);

        $this->logger->shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $extra) {
                $this->assertSame('Feedback data failed validation', $message);
                $this->assertSame('FEEDBACK_VALIDATION_FAILED', $extra['error_code']);
                $this->assertSame(500, $extra['status']);
                return true;
            });

        $this->assertFalse($this->sut->add($data));
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
