<?php

namespace Application\Model\Service\Feedback;

use Laminas\Validator\EmailAddress as EmailAddressValidator;
use Laminas\Validator\InArray as InArrayValidator;
use Laminas\Validator\StringLength as StringLengthValidator;

class FeedbackValidator
{
    public const MANDATORY_FIELDS = [
        'rating',
        'details'
    ];

    public const OPTIONAL_FIELDS = [
        'agent',
        'fromPage',
        'email',
        'phone'
    ];

    // matches service-front FeedbackForm.php radio button values,
    // which are not centrally stored anywhere, so replicated here
    public const VALID_RATINGS = [
        'very-satisfied',
        'satisfied',
        'neither-satisfied-or-dissatisfied',
        'dissatisfied',
        'very-dissatisfied',
    ];

    // matches service-front FeedbackForm.php $maxFeedbackLength
    public const MAX_DETAILS_LENGTH = 2000;

    private array $validators = [];

    /**
     * Constructor
     *
     * @param array $validators Validators to apply to fields;
     *     keys are field names, values are Laminas\Validator
     *     instances. If a validator is not set for a field,
     *     a default is used.
     */
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

        if (!isset($validators['email'])) {
            $validators['email'] = new EmailAddressValidator();
        }

        if (!isset($validators['phone'])) {
            $validators['phone'] = new StringLengthValidator(['min' => 1]);
        }

        if (!isset($validators['agent'])) {
            $validators['agent'] = new StringLengthValidator(['min' => 1]);
        }

        if (!isset($validators['fromPage'])) {
            $validators['fromPage'] = new StringLengthValidator(['min' => 1]);
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
     *
     * @param array $feedbackData Data to be validated; fieldnames
     *    outside of MANDATORY_FIELDS + OPTIONAL_FIELDS are ignored
     * @return bool true if valid, else false
     */
    public function isValid(array $feedbackData): bool
    {
        // force any empty mandatory fields to null
        foreach (self::MANDATORY_FIELDS as $fieldName) {
            $feedbackData[$fieldName] ??= null;
        }

        // remove any optional fields which are not set or contain an empty string
        foreach (self::OPTIONAL_FIELDS as $fieldName) {
            if (!isset($feedbackData[$fieldName]) || $feedbackData[$fieldName] === '') {
                unset($feedbackData[$fieldName]);
            }
        }

        // validate any remaining fields
        $valid = true;

        foreach ($feedbackData as $fieldName => $fieldValue) {
            $valid = isset($this->validators[$fieldName]) &&
                $this->validators[$fieldName]->isValid($fieldValue);

            if (!$valid) {
                break;
            }
        }

        return $valid;
    }
}
