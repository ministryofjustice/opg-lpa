<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

/**
 * @template T
 * @template-extends AbstractMainFlowForm<T>
 */
class HowAttorneysMakeDecisionForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'how' => [
            'type'    => 'Laminas\Form\Element\Radio',
            'attributes' => [
                'id'             => 'how',
                'div-attributes' => ['class' => 'multiple-choice'],
            ],
            'required' => true,
            'options'  => [
                'value_options' => [
                    'jointly-attorney-severally' => ['value' => 'jointly-attorney-severally'],
                    'jointly'                    => ['value' => 'jointly'],
                    'depends'                    => ['value' => 'depends'],
                ],
            ],
        ],
        'howDetails' => [
            'required' => true,
            'type'     => 'Textarea',
        ],
    ];

    public function init()
    {
        $this->setName('form-primary-attorney-decisions');
        parent::init();
    }

    protected function validateByModel()
    {
        $decision   = new PrimaryAttorneyDecisions($this->data);
        $validation = $decision->validate(['how']);

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
