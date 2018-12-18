<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class HowAttorneysMakeDecisionForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'how' => [
            'type'     => 'Radio',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
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
            'type'     => 'Textarea',
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
    protected function validateByModel()
    {
        $decision = new PrimaryAttorneyDecisions($this->data);

        $validation = $decision->validate(['how']);

        $messages = [];

        if ($validation->hasErrors()) {
            $messages = $this->modelValidationMessageConverter($validation);
        }

        return [
            'isValid'  => !$validation->hasErrors(),
            'messages' => $messages,
        ];
    }
}
