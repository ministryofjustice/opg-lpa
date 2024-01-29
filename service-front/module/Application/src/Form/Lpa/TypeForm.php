<?php

namespace Application\Form\Lpa;

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
                'id' => 'type',
                'div-attributes' => ['class' => 'multiple-choice'],
            ],
            'options'       => [
                'value_options' => [
                    'property-and-financial' => 'Property and financial affairs',
                    'health-and-welfare' => 'Health and welfare',
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
     * Validate form input data through model validators
     *
     * @return array
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
