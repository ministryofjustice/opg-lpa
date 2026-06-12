<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use MakeShared\DataModel\Validator\ValidatorResponse;
use MakeShared\DataModel\WhoAreYou\WhoAreYou;

/**
 * @template T
 * @template-extends AbstractMainFlowForm<T>
 */
class WhoAreYouForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'who' => [
            'type'    => 'Radio',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'options' => [
                'value_options' => [
                    'donor'                      => ['value' => 'donor'],
                    'friendOrFamily'             => ['value' => 'friendOrFamily'],
                    'financeProfessional'        => ['value' => 'financeProfessional'],
                    'legalProfessional'          => ['value' => 'legalProfessional'],
                    'estatePlanningProfessional' => ['value' => 'estatePlanningProfessional'],
                    'digitalPartner'             => ['value' => 'digitalPartner'],
                    'charity'                    => ['value' => 'charity'],
                    'organisation'               => ['value' => 'organisation'],
                    'other'                      => ['value' => 'other'],
                    'notSaid'                    => ['value' => 'notSaid'],
                ],
            ],
        ],
        'other' => [
            'type' => 'Text',
        ],
    ];

    public function init()
    {
        $this->setName('form-who-are-you');
        parent::init();
    }

    protected function validateByModel()
    {
        $whoAreYou  = new WhoAreYou($this->convertFormDataForModel($this->data));
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

    protected function convertFormDataForModel($formData)
    {
        $modelData = [];

        if (array_key_exists('who', $formData) && array_key_exists($formData['who'], WhoAreYou::options())) {
            $who       = $formData['who'];
            $qualifier = null;

            if ($who == 'other') {
                $qualifier = $formData['other'];
            }

            $modelData = [
                'who'       => $who,
                'qualifier' => $qualifier,
            ];
        }

        return $modelData;
    }

    protected function modelValidationMessageConverter(ValidatorResponse $validationResponse, $context = null)
    {
        $messages = [];

        foreach ($validationResponse as $validationErrorKey => $validationErrors) {
            if ($validationErrorKey == 'subquestion') {
                // no-op for 'other' case
            } elseif ($validationErrorKey == 'qualifier') {
                if (isset($context['who']) && $context['who'] == 'organisation') {
                    $fieldName = 'organisation';
                }
            } else {
                $fieldName = $validationErrorKey;
            }

            if (isset($fieldName)) {
                $messages[$fieldName] = $validationErrors['messages'];
            }
        }

        return $messages;
    }
}
