<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use Opg\Lpa\DataModel\Validator\ValidatorResponse;

class WhoAreYouForm extends AbstractLpaForm
{
    protected $formElements = [
        'who' => [
            'type' => 'Radio',
            'options' => [
                'value_options' => [
                    'donor' => [
                        'value' => 'donor',
                    ],
                    'friendOrFamily' => [
                        'value' => 'friendOrFamily',
                    ],
                    'professional'   => [
                        'value' => 'professional',
                    ],
                    'digitalPartner'      => [
                        'value' => 'digitalPartner',
                    ],
                    'organisation'  => [
                        'value' => 'organisation',
                    ],
                    'notSaid'  => [
                        'value' => 'notSaid'
                    ],
                ],
            ],
        ],
        'professional' => [
            'type' => 'Radio',
            'options' => [
                'value_options' => [
                    'solicitor'      => [
                        'value' => 'solicitor',
                    ],
                    'will-writer'      => [
                        'value' => 'will-writer',
                    ],
                    'other'      => [
                        'value' => 'other',
                    ],
                ],
            ],
        ],
        'professional-other' => [
            'type' => 'Text'
        ],
        'organisation' => [
            'type' => 'Text'
        ],
        'submit' => [
            'type' => 'Submit',
        ],
    ];

    public function init()
    {
        $this->setName('form-who-are-you');

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    protected function validateByModel()
    {
        $whoAreYou = new WhoAreYou($this->convertFormDataForModel($this->data));

        $validation = $whoAreYou->validate();

        $messages = [];

        if ($validation->hasErrors()) {
            $messages = $this->modelValidationMessageConverter($validation, $this->data);
        }

        return [
            'isValid'  => !$validation->hasErrors(),
            'messages' => $messages,
        ];
    }

    /**
     * Convert form data to model-compatible input data format.
     *
     * @param array $formData. e.g. ['who'=>'professional','professional'=>'solicitor', 'professional-other'=>null, 'digitalPartner'=>null, 'orgaisation'=>null]
     *
     * @return array. e.g. ['who'=>'prefessional', 'subquestion'=>'solicitor', 'qualifier'=>null]
     */
    protected function convertFormDataForModel($formData)
    {
        $modelData = [];

        if (array_key_exists($formData['who'], WhoAreYou::options())) {
            //  Get the form data
            $who = $formData['who'];

            //  Set the default model data
            $subQuestion = null;
            $qualifier = null;

            //  Set the model data accordingly
            if ($who == 'professional') {
                $subQuestion = $formData['professional'];

                if ($subQuestion == 'other') {
                    $qualifier = $formData['professional-other'];
                }
            } elseif ($who == 'organisation') {
                $qualifier = $formData['organisation'];
            }

            //  Set the model data
            $modelData = [
                'who'         => $who,
                'subquestion' => $subQuestion,
                'qualifier'   => $qualifier,
            ];
        }

        return $modelData;
    }

    /**
     * Convert model validation response to Zend Form validation messages format.
     */
    protected function modelValidationMessageConverter(ValidatorResponse $validation, $context = null)
    {
        $messages = [];

        // loop through all form elements.
        foreach ($validation as $validationErrorKey => $validationErrors) {
            if ($validationErrorKey == 'subquestion') {
                switch ($context['who']) {
                    case 'professional':
                        $fieldName = 'professional';
                        break;
                    case 'organisation':
                        break;
                    default:
                }
            } elseif ($validationErrorKey == 'qualifier') {
                switch ($context['who']) {
                    case 'professional':
                        $fieldName = 'professional-other';
                        break;
                    case 'organisation':
                        $fieldName = 'organisation';
                        break;
                    default:
                }
            } else {
                $fieldName = $validationErrorKey;
            }

            $messages[$fieldName] = $validationErrors['messages'];
        }

        return $messages;
    }
}
