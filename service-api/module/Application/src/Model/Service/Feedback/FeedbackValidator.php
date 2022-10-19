<?php

namespace Application\Model\Service\Feedback;

use Laminas\Validator\InArray as InArrayValidator;

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

    private array $validators = [];

    public function __construct(array $validators = [])
    {
        if (!isset($validators['rating'])) {
            $validators['rating'] = new InArrayValidator([
                'haystack' => self::VALID_RATINGS
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

        return true;
    }
}
