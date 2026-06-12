<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use MakeShared\DataModel\Lpa\Lpa;
use Laminas\Validator\StringLength;

/**
 * @template T
 * @template-extends AbstractMainFlowForm<T>
 */
class RepeatApplicationForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'isRepeatApplication' => [
            'type'     => 'Laminas\Form\Element\Radio',
            'attributes' => [
                'id'             => 'isRepeatApplication',
                'div-attributes' => ['class' => 'govuk-radios__item'],
            ],
            'required' => true,
            'options'  => [
                'value_options' => [
                    'is-repeat' => ['value' => 'is-repeat'],
                    'is-new'    => ['value' => 'is-new'],
                ],
            ],
        ],
        'repeatCaseNumber' => [
            'type'    => 'Text',
            'required' => true,
            'filters' => [
                [
                    'name'    => 'Laminas\Filter\Word\DashToSeparator',
                    'options' => ['separator' => ''],
                ],
            ],
            'validators' => [
                ['name' => 'Digits'],
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max'      => 12,
                        'messages' => [StringLength::TOO_LONG => 'max-length-%max%'],
                    ],
                ],
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'min'      => 12,
                        'messages' => [StringLength::TOO_SHORT => 'min-length-%min%'],
                    ],
                ],
            ],
        ],
    ];

    public function init()
    {
        $this->setName('form-repeat-application');
        parent::init();
    }

    protected function validateByModel()
    {
        $isValid  = true;
        $messages = [];

        if ($this->data['isRepeatApplication'] == 'is-repeat') {
            $lpa = new Lpa([
                'repeatCaseNumber' => (int) $this->data['repeatCaseNumber'],
            ]);

            $validation = $lpa->validate(['repeatCaseNumber']);
            $isValid    = !$validation->hasErrors();

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
