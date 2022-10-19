<?php

namespace ApplicationTest\Model\Service\Feedback;

use Application\Model\Service\Feedback\FeedbackValidator;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class FeedbackValidatorTest extends MockeryTestCase
{
    private FeedbackValidator $sut;

    public function setUp(): void
    {
        $this->sut = new FeedbackValidator();
    }

    public function testRatingNotSet()
    {
        $this->assertFalse($this->sut->isValid([]));
    }

    public function testRatingOutsideValidRange()
    {
        $this->assertFalse($this->sut->isValid(['rating' => 'foo']));
    }

    public function testRatingValid()
    {
        $this->assertTrue($this->sut->isValid(['rating' => 'satisfied']));
    }
}
