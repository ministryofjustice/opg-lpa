<?php
namespace Application\Form\Lpa;

use Zend\Validator\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;

class PaymentForm extends AbstractForm
{
    protected $formElements = [
            'method' => [
                    'type'      => 'Zend\Form\Element\Radio',
                    'required'  => true,
                    'options'   => [
                            'value_options' => [
                                    'card' => [
                                            'value' => 'card',
                                    ],
                                    'cheque' => [
                                            'value' => 'cheque',
                                    ],
                            ],
                    ],
            ],
            'email' => [
                    'required' => true,
                    'type' => 'Email',
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function init ()
    {
        // The email value is only used for sending to payment gateway, therefore it is not validated by model.
        $this->formElements['email']['validators'] = [new EmailAddress()];
        
        $this->setName('payment');
        
        parent::init();
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $payment = new Payment(['method'=>$this->data['method']]);
        
        $validation = $payment->validate(['method']);
        
        if(count($validation) == 0) {
            return ['isValid'=>true, 'messages' => []];
        }
        else {
            return [
                    'isValid'=>false,
                    'messages' => $this->modelValidationMessageConverter($validation),
            ];
        }
    }
}
