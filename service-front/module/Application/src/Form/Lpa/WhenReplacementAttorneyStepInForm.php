<?php

namespace Application\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

/**
 * @template T
 * @template-extends AbstractMainFlowForm<T>
 */

class WhenReplacementAttorneyStepInForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'when' => [
            'type'      => 'Laminas\Form\Element\Radio',
            'attributes' => [
                'id' => 'when',
                'div-attributes' => ['class' => 'multiple-choice']
            ],
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
            'type'     => 'Textarea',
            'required' => true,
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
    protected function validateByModel()
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
