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

    public function testRatingNotSet(): void
    {
        $this->assertFalse($this->sut->isValid([]));
    }

    public function testRatingOutsideValidRange(): void
    {
        $this->assertFalse($this->sut->isValid(['rating' => 'foo']));
    }

    public function testDetailsNotSet(): void
    {
        $feedbackData = [
            'rating' => 'satisfied',
        ];

        $this->assertFalse($this->sut->isValid($feedbackData));
    }

    public function testDetailsNullOrEmptyOrNotString(): void
    {
        $feedbackData = [
            'rating' => 'satisfied',
            'details' => null,
        ];

        $this->assertFalse($this->sut->isValid($feedbackData));

        $feedbackData = [
            'rating' => 'satisfied',
            'details' => '',
        ];

        $this->assertFalse($this->sut->isValid($feedbackData));

        $feedbackData = [
            'rating' => 'satisfied',
            'details' => 4,
        ];

        $this->assertFalse($this->sut->isValid($feedbackData));
    }

    public function testDetailsTooLong(): void
    {
        $feedbackData = [
            'rating' => 'satisfied',

            // 2001 characters, 1 character over max length for details field
            'details' => 'a' . str_repeat('a ', 1000),
        ];

        $this->assertFalse($this->sut->isValid($feedbackData));
    }

    public function testValid(): void
    {
        $feedbackData = [
            'rating' => 'satisfied',

            // at max length for the details field
            'details' => str_repeat('c ', 1000),
        ];

        $this->assertTrue($this->sut->isValid($feedbackData));
    }
}
