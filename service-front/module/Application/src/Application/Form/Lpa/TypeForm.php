<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Document;

class TypeForm extends AbstractLpaForm
{
    protected $formElements = [
        'type' => [
            'type'      => 'Application\Form\Element\Type',
            'required'  => true,
        ],
        'submit' => [
            'type' => 'Submit',
        ],
    ];

    public function init()
    {
        $this->setName('form-type');

        $this->setUseInputFilterDefaults(false);

        $inputFilter = $this->getInputFilter();

        $inputFilter->add([
            'name'     => 'type',
            'required' => true,
            'error_message' => 'cannot-be-empty',
        ]);

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
