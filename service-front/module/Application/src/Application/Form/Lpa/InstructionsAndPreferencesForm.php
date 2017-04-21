<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Document;

class InstructionsAndPreferencesForm extends AbstractLpaForm
{
    protected $formElements = [
        'instruction' => [
            'type' => 'Textarea',
        ],
        'preference' => [
            'type' => 'Textarea',
        ],
        'submit' => [
            'type' => 'Submit',
        ],
    ];

    public function init()
    {
        $this->setName('form-instructions-and-preferences');

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    public function validateByModel()
    {
        $document = new Document($this->data);

        $validation = $document->validate(['instructions, preferences']);

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
