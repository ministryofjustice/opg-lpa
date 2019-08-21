<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use Opg\Lpa\DataModel\Validator\ValidatorResponse;

class WhoAreYouForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'who' => [
            'type' => 'Radio',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'options' => [
                'value_options' => [
                    'donor' => [
                        'value' => 'donor',
                    ],
                    'friendOrFamily' => [
                        'value' => 'friendOrFamily',
                    ],
                    'financeProfessional'   => [
                        'value' => 'financeProfessional',
                    ],
                    'legalProfessional'   => [
                        'value' => 'legalProfessional',
                    ],
                    'estatePlanningProfessional'   => [
                        'value' => 'estatePlanningProfessional',
                    ],
                    'digitalPartner'      => [
                        'value' => 'digitalPartner',
                    ],
                    'charity'  => [
                        'value' => 'charity',
                    ],
                    'organisation'  => [
                        'value' => 'organisation',
                    ],
                    'other'  => [
                        'value' => 'other',
                    ],
                    'notSaid'  => [
                        'value' => 'notSaid'
                    ],
                ],
            ],
        ],
        'other' => [
            'type' => 'Text'
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
     * @param array $formData. e.g. ['who'=>'other', 'digitalPartner'=>null, 'other'=>null]
     *
     * @return array. e.g. ['who'=>'prefessional', 'subquestion'=>'solicitor', 'qualifier'=>null]
     */
    protected function convertFormDataForModel($formData)
    {
        $modelData = [];

        if (array_key_exists('who', $formData) && array_key_exists($formData['who'], WhoAreYou::options())) {
            //  Get the form data
            $who = $formData['who'];

            //  Set the default model data
            $qualifier = null;

            //  Set the model data accordingly
            if ($who == 'other') {
                $qualifier = $formData['other'];
            }

            //  Set the model data
            $modelData = [
                'who'         => $who,
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
                    case 'other':
                        break;
                    default:
                }
            } elseif ($validationErrorKey == 'qualifier') {
                switch ($context['who']) {
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
