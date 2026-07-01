<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

/**
 * @template T
 * @template-extends AbstractMainFlowForm<T>
 */
class LifeSustainingForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'canSustainLife' => [
            'type'     => 'Radio',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'required' => true,
            'options'  => [
                'value_options' => [
                    'true'  => ['value' => '1'],
                    'false' => ['value' => '0'],
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
     * @return (array|bool|mixed)[]
     *
     * @psalm-return array{isValid: bool, messages: array<never, never>|mixed}
     */
    protected function validateByModel()
    {
        $decisions  = new PrimaryAttorneyDecisions($this->convertFormDataForModel($this->data));
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
