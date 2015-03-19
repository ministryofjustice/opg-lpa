<?php
namespace Application\Form\Lpa;

use Zend\Validator\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Lpa\Lpa;

class FeeForm extends AbstractForm
{
    /**
     * @var Lpa $lpa
     */
    private $lpa;
    
    protected $formElements = [
            'repeatCaseNumber' => [
                    'type' => 'Text',
            ],
            'reducedFeeReceivesBenefits' => [
                    'type' => 'Checkbox',
                    'options' => [
                            'checked_value' => true,
                            'use_hidden_element' => false
                    ],
            ],
            'reducedFeeAwardedDamages' => [
                    'type' => 'Checkbox',
                    'options' => [
                            'checked_value' => true,
                            'use_hidden_element' => false
                    ],
            ],
            'reducedFeeLowIncome' => [
                    'type' => 'Checkbox',
                    'options' => [
                            'checked_value' => true,
                            'use_hidden_element' => false
                    ],
            ],
            'reducedFeeUniversalCredit' => [
                    'type' => 'Checkbox',
                    'options' => [
                            'checked_value' => true,
                            'use_hidden_element' => false
                    ],
            ],
            'method' => [
                    'type' => 'Checkbox',
                    'options' => [
                            'checked_value' => 'cheque',
                            'unchecked_value' => 'card',
                            'use_hidden_element' => true
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
    
    public function __construct (Lpa $lpa, $formName = 'type-form')
    {
        $this->lpa = $lpa;
        
        // The email value is only used for sending to payment gateway, therefore it is not validated by model.
        $this->formElements['email']['validators'] = [new EmailAddress()];
        
        parent::__construct($formName);
        
    }
    
    public function validateByModel()
    {
        $payment = new Payment($this->convertFormDataForModel($this->data));
        
        $validation = $payment->validate();
        
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
