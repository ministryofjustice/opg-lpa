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

    public function testNoData(): void
    {
        $this->assertFalse($this->sut->isValid([]));
    }

    public function testRatingNotSet(): void
    {
        $this->assertFalse($this->sut->isValid(['details' => 'foo']));
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

    public function testEmailProvidedAsEmptyString(): void
    {
        $feedbackData = [
            'rating' => 'satisfied',
            'details' => 'very good',
            'email' => '',
        ];

        $this->assertTrue(
            $this->sut->isValid($feedbackData),
            'empty email string should be allowed and not be validated'
        );
    }

    public function testEmailProvidedButInvalidPattern(): void
    {
        $feedbackData = [
            'rating' => 'satisfied',
            'details' => 'very good',
            'email' => 'notanemail',
        ];

        $this->assertFalse(
            $this->sut->isValid($feedbackData),
            'non-empty email string should resemble an email address'
        );
    }

    // tests for optional fields which can be not set, set to the empty
    // string, or must be a valid string (email if field == 'email',
    // non-empty string otherwise etc.)
    public function testOptionalFieldValidation(): void
    {
        $feedbackDataPristine = [
            'rating' => 'satisfied',
            'details' => 'very good',

            // optional fields we're testing
            'email' => 'email@emil.com',
            'fromPage' => '/home',
            'agent' => 'Firefox',
            'phone' => '0111 111 1111',
        ];

        // optional fields...
        foreach (FeedbackValidator::OPTIONAL_FIELDS as $fieldName) {
            $feedbackData = array_merge($feedbackDataPristine, []);

            // ...can have an empty string in them and be ignored
            $feedbackData[$fieldName] = '';

            $this->assertTrue(
                $this->sut->isValid($feedbackData),
                "empty string in $fieldName should be allowed and not be validated"
            );

            // ...or be null and be ignored
            $feedbackData[$fieldName] = null;

            $this->assertTrue(
                $this->sut->isValid($feedbackData),
                "null in $fieldName should be allowed and not be validated"
            );

            // ...or not be set at all and be ignored
            unset($feedbackData[$fieldName]);

            $this->assertTrue(
                $this->sut->isValid($feedbackData),
                "not set $fieldName should be allowed and not be validated"
            );

            // ...or will fail validation if they have a non-valid value
            // (for our purposes here, we use an array, as none of these fields
            // should accept an array)
            $feedbackData[$fieldName] = [];

            $this->assertFalse(
                $this->sut->isValid($feedbackData),
                "$fieldName has a non-valid value and validation should fail"
            );
        }
    }

    public function testValid(): void
    {
        $feedbackData = [
            'rating' => 'satisfied',

            // at max length for the details field
            'details' => str_repeat('c ', 1000),

            'email' => 'emilfoo@example.com',
            'phone' => '0111 2121 9999 extension 110',
            'agent' => 'Firefox',
        ];

        $this->assertTrue($this->sut->isValid($feedbackData));
    }
}
