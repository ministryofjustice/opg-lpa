<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;

class RepeatApplicationForm extends AbstractLpaForm
{
    protected $formElements = [
        'isRepeatApplication' => [
            'type'      => 'Radio',
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
                    'name' => 'Word\DashToSeparator',
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
        'submit' => [
            'type' => 'Submit',
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
    public function validateByModel()
    {
        $isValid = ($this->data['isRepeatApplication'] == 'is-new');
        $messages = [];

        if ($this->data['isRepeatApplication'] == 'is-repeat') {
            //  Create an LPA and validate it with the validation in the data models
            $lpaData = [
                'repeatCaseNumber' => (int) $this->data['repeatCaseNumber'],
            ];

            $lpa = new Lpa($lpaData);

            $validation = $lpa->validate(['repeatCaseNumber']);

            if (count($validation) == 0) {
                $isValid = true;
            } else {
                $isValid = false;
                $messages = $this->modelValidationMessageConverter($validation);
            }
        }

        return [
            'isValid'  => $isValid,
            'messages' => $messages,
        ];
    }
}
