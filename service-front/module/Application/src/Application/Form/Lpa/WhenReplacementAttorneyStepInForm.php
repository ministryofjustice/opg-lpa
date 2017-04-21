<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class WhenReplacementAttorneyStepInForm extends AbstractLpaForm
{
    protected $formElements = [
        'when' => [
            'type'      => 'Radio',
            'required'  => true,
            'options'   => [
                'value_options' => [
                    'first'  => [
                        'value' => 'first',
                    ],
                    'last'   => [
                        'value' => 'last',
                    ],
                    'depends' => [
                        'value' => 'depends'
                    ],
                ],
            ],
        ],
        'whenDetails' => [
            'type'     => 'TextArea',
            'required' => true,
        ],
        'submit' => [
            'type' => 'Submit',
        ],
    ];

    public function init()
    {
        $this->setName('form-when-replacement-attonrey-step-in');

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    public function validateByModel()
    {
        $document = new ReplacementAttorneyDecisions($this->data);

        $validation = $document->validate(['when']);

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
