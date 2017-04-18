<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class HowAttorneysMakeDecisionForm extends AbstractLpaForm
{
    protected $formElements = [
        'how' => [
            'type'     => 'Radio',
            'required' => true,
            'options'  => [
                'value_options' => [
                    'jointly-attorney-severally' => [
                        'value' => 'jointly-attorney-severally',
                    ],
                    'jointly' => [
                        'value' => 'jointly',
                    ],
                    'depends' => [
                        'value' => 'depends',
                    ],
                ],
            ],
        ],
        'howDetails' => [
            'required' => true,
            'type'     => 'TextArea',
        ],
        'submit' => [
            'type' => 'Submit',
        ],
    ];

    public function init()
    {
        $this->setName('form-primary-attorney-decisions');

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    public function validateByModel()
    {
        $decision = new PrimaryAttorneyDecisions($this->data);

        $validation = $decision->validate(['how']);

        $isValid = true;
        $messages = [];

        if (count($validation) != 0) {
            $isValid = false;
            $messages = $this->modelValidationMessageConverter($validation);
        }

        return [
            'isValid'  => $isValid,
            'messages' => $messages,
        ];
    }
}
