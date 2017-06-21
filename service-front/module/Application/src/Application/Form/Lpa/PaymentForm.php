<?php

namespace Application\Form\Lpa;

use Application\Form\AbstractCsrfForm;

class PaymentForm extends AbstractCsrfForm
{
    /**
     * PaymentForm constructor
     *
     * @param null $name
     * @param array $options
     */
    public function __construct($name = null, $options = [])
    {
        parent::__construct('form-payment', $options);

        $this->add([
            'name' => 'email',
            'type' => 'Email',
        ]);

        $this->addToInputFilter([
            'name'     => 'email',
            'required' => true,
            'validators' => [
                [
                    'name' => 'EmailAddress',
                ],
            ],
        ]);

        //  Add the submit button
        $this->add([
            'name'  => 'submit',
            'type'  => 'Submit',
        ]);
    }
}
