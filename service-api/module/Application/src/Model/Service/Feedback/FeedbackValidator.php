<?php

namespace Application\Model\Service\Feedback;

use Laminas\Validator\InArray as InArrayValidator;
use Laminas\Validator\StringLength as StringLengthValidator;

class FeedbackValidator
{
    // matches service-front FeedbackForm.php radio button values,
    // which are not centrally stored anywhere, so replicated here
    /** @var array */
    public const VALID_RATINGS = [
        'very-satisfied',
        'satisfied',
        'neither-satisfied-or-dissatisfied',
        'dissatisfied',
        'very-dissatisfied',
    ];

    // matches service-front FeedbackForm.php $maxFeedbackLength
    /** @var int */
    public const MAX_DETAILS_LENGTH = 2000;

    private array $validators = [];

    public function __construct(array $validators = [])
    {
        if (!isset($validators['rating'])) {
            $validators['rating'] = new InArrayValidator([
                'haystack' => self::VALID_RATINGS
            ]);
        }

        if (!isset($validators['details'])) {
            $validators['details'] = new StringLengthValidator([
                'min' => 1, 'max' => self::MAX_DETAILS_LENGTH
            ]);
        }

        $this->validators = $validators;
    }

    /**
     * Validate the $feedbackData array.
     *
     * The only required fields are 'details' and 'rating'.
     *
     * Other fields, if present, are validated to ensure they
     * have the correct data types.
     */
    public function isValid(array $feedbackData): bool
    {
        if (!isset($feedbackData['rating'])) {
            return false;
        }

        if (!$this->validators['rating']->isValid($feedbackData['rating'])) {
            return false;
        }

        if (!isset($feedbackData['details'])) {
            return false;
        }

        if (!$this->validators['details']->isValid($feedbackData['details'])) {
            return false;
        }

        return true;
    }
}
