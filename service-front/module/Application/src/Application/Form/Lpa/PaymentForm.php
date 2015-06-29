<?php
namespace Application\Form\Lpa;

use Zend\Validator\EmailAddress;

class PaymentForm extends AbstractForm
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
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function init ()
    {
        // The email value is only used for sending to payment gateway, therefore it is not validated by model.
        $this->formElements['email']['validators'] = [new EmailAddress()];
        
        $this->setName('form-payment');
        
        parent::init();
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        return ['isValid'=>true, 'messages' => []];
    }
}
