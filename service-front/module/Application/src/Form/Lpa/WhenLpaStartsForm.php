<?php

namespace Application\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

/**
 * @template T
 * @template-extends AbstractMainFlowForm<T>
 */

class WhenLpaStartsForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'when' => [
            'type'      => 'Laminas\Form\Element\Radio',
            'required'  => true,
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'options'   => [
                'value_options' => [
                    'now' => [
                        'id' => 'when',
                        'value' => 'now',
                        'label' => 'as soon as it\'s registered (with the donor\'s consent)',
                        'label_attributes' => [
                            'class' => 'block-label',
                        ],
                    ],
                    'no-capacity' => [
                        'value' => 'no-capacity',
                        'label' => 'only if the donor does not have mental capacity',
                        'label_attributes' => [
                            'class' => 'block-label',
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function init()
    {
        $this->setName('form-when-lpa-starts');

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    protected function validateByModel()
    {
        $decisions = new PrimaryAttorneyDecisions($this->data);

        $validation = $decisions->validate(['when']);

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
