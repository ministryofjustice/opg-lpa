<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

/**
 * @template T
 * @template-extends AbstractMainFlowForm<T>
 */
class WhenReplacementAttorneyStepInForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'when' => [
            'type'     => 'Laminas\Form\Element\Radio',
            'attributes' => [
                'id'             => 'when',
                'class'          => 'govuk-radios__input',
                'div-attributes' => ['class' => 'govuk-radios__item'],
            ],
            'required' => true,
            'options'  => [
                'value_options' => [
                    'first'  => ['value' => 'first'],
                    'last'   => ['value' => 'last'],
                    'depends' => ['value' => 'depends'],
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
     * @return (array|bool|mixed)[]
     *
     * @psalm-return array{isValid: bool, messages: array<never, never>|mixed}
     */
    protected function validateByModel()
    {
        $document   = new ReplacementAttorneyDecisions($this->data);
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
