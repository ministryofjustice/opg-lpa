<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\Document;

/**
 * @template T
 * @template-extends AbstractMainFlowForm<T>
 */
class TypeForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'type' => [
            'type'          => 'Laminas\Form\Element\Radio',
            'required'      => true,
            'error_message' => 'cannot-be-empty',
            'attributes'    => [
                'id'             => 'type',
                'class'          => 'govuk-radios__input',
                'div-attributes' => ['class' => 'govuk-radios__item'],
            ],
            'options'       => [
                'value_options' => [
                    'property-and-financial' => 'Property and financial affairs',
                    'health-and-welfare'     => 'Health and welfare',
                ],
            ],
        ],
    ];

    public function init()
    {
        $this->setName('form-type');
        $this->setUseInputFilterDefaults(false);
        parent::init();
    }

    /**
     * @return (array|bool|mixed)[]
     *
     * @psalm-return array{isValid: bool, messages: array<never, never>|mixed}
     */
    protected function validateByModel()
    {
        $document = new Document($this->data);
        $validation = $document->validate(['type']);

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
