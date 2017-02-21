<?php
namespace Application\Form\General;

use Zend\Validator\NotEmpty;
use Zend\Validator\StringLength;

/**
 * To send feedback to the OPG
 *
 * Class Feedback
 * @package Application\Form\General
 */
class FeedbackForm extends AbstractForm
{
    private $maxFeedbackLength = 2000;

    public function __construct($formName = null)
    {

        parent::__construct('send-feedback');

        $valueOptions = [
            'very-satisfied' => [
                'value' => 'very-satisfied',
            ],
            'satisfied' => [
                'value' => 'satisfied',
            ],
            'neither-satisfied-or-dissatisfied' => [
                'value' => 'neither-satisfied-or-dissatisfied',
            ],
            'dissatisfied' => [
                'value' => 'dissatisfied',
            ],
            'very-dissatisfied' => [
                'value' => 'very-dissatisfied',
            ],
        ];

        $this->add([
            'name' => 'rating',
            'type' => 'Radio',
            'options'   => [
                'value_options' => $valueOptions,
                'disable_inarray_validator' => true,
            ],
        ]);

        $this->add([
            'name' => 'details',
            'type' => 'Textarea',
        ]);

        $this->add([
            'name' => 'email',
            'type' => 'Email',
        ]);

        $this->add([
            'name' => 'phone',
            'type' => 'Text',
        ]);

        $inputFilter = $this->getInputFilter();

        $inputFilter->add([
            'name'     => 'rating',
            'error_message' => 'cannot-be-empty',
        ]);

        $inputFilter->add([
            'name'     => 'details',
            'filters'  => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name'    => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ],
                ],
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => $this->maxFeedbackLength,
                        'messages' => [
                             StringLength::TOO_LONG => 'max-' . $this->maxFeedbackLength . '-chars',
                         ],
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name'     => 'email',
            'required' => false,
            'filters'  => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                ['name' => 'EmailAddress'],
            ],
        ]);

        $inputFilter->add([
            'name'     => 'phone',
            'required' => false,
            'filters'  => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
        ]);

        $this->setInputFilter($inputFilter);
    }
}
