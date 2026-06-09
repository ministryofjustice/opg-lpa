<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\Document;

/**
 * @template T
 * @template-extends AbstractMainFlowForm<T>
 */
class InstructionsAndPreferencesForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'instruction' => [
            'type' => 'Textarea',
        ],
        'preference' => [
            'type' => 'Textarea',
        ],
    ];

    public function init()
    {
        $this->setName('form-preferences-and-instructions');
        parent::init();
    }

    protected function validateByModel()
    {
        $document   = new Document($this->data);
        $validation = $document->validate(['instruction', 'preference']);

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
