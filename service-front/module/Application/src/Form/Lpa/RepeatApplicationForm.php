<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;

class RepeatApplicationForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'isRepeatApplication' => [
            'type'      => 'Radio',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'required'  => true,
            'options'   => [
                'value_options' => [
                    'is-repeat' => [
                        'value' => 'is-repeat',
                    ],
                    'is-new' => [
                        'value' => 'is-new',
                    ],
                ],
            ],
        ],
        'repeatCaseNumber' => [
            'type' => 'Text',
            'required'  => true,
            'filters'  => [
                [
                    'name' => 'Zend\Filter\Word\DashToSeparator',
                    'options' => [
                        'separator' => '',
                    ]
                ],
            ],
            'validators' => [
                [
                    'name' => 'Digits',
                ]
            ],
        ],
    ];

    public function init()
    {
        $this->setName('form-repeat-application');

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    protected function validateByModel()
    {
        $isValid = true;
        $messages = [];

        //  If this is a repeat application validate the repeat case number
        if ($this->data['isRepeatApplication'] == 'is-repeat') {
            //  Create an LPA and validate it with the validation in the data models
            $lpa = new Lpa([
                'repeatCaseNumber' => (int) $this->data['repeatCaseNumber'],
            ]);

            $validation = $lpa->validate(['repeatCaseNumber']);
            $isValid = !$validation->hasErrors();

            if ($validation->hasErrors()) {
                $messages = $this->modelValidationMessageConverter($validation);
            }
        }

        return [
            'isValid'  => $isValid,
            'messages' => $messages,
        ];
    }
}
