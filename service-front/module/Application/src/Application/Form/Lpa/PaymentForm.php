<?php

namespace Application\Form\Lpa;

class PaymentForm extends AbstractLpaForm
{
    protected $formElements = [
        'email' => [
            'required' => true,
            'type' => 'Email',
            'validators' => [
                [
                    'name' => 'EmailAddress',
                ]
            ],
        ],
        'submit' => [
            'type' => 'Submit',
        ],
    ];

    public function init()
    {
        $this->setName('form-payment');

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    protected function validateByModel()
    {
        return [
            'isValid' => true,
            'messages' => []
        ];
    }
}
