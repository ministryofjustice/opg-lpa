<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class LifeSustainingForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'canSustainLife' => [
            'type'      => 'Radio',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'required'  => true,
            'options'   => [
                'value_options' => [
                    true => [
                        'value' => '1',
                    ],
                    false => [
                        'value' => '0',
                    ],
                ],
            ],
        ],
    ];

    public function init()
    {
        $this->setName('form-life-sustaining');

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    protected function validateByModel()
    {
        $decisions = new PrimaryAttorneyDecisions($this->convertFormDataForModel($this->data));

        $validation = $decisions->validate(['canSustainLife']);

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
