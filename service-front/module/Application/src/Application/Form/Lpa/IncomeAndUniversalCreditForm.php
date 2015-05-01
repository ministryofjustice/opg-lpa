<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Payment\Payment;

class IncomeAndUniversalCreditForm extends AbstractForm
{
    protected $formElements = [
            'reducedFeeUniversalCredit' => [
                    'type'      => 'Zend\Form\Element\Radio',
                    'required'  => true,
                    'options'   => [
                            'value_options' => [
                                    'yes' => [
                                            'value' => 1,
                                    ],
                                    'no' => [
                                            'value' => 0,
                                    ],
                            ],
                    ],
            ],
            'reducedFeeLowIncome' => [
                    'type'      => 'Zend\Form\Element\Radio',
                    'required'  => true,
                    'options'   => [
                            'value_options' => [
                                    'yes' => [
                                            'value' => 1,
                                    ],
                                    'no' => [
                                            'value' => 0,
                                    ],
                            ],
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function init()
    {
        $this->setName('income-and-universal-credit');
        parent::init();
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $lpa = new Payment([
                'reducedFeeLowIncome'       => (bool)$this->data['reducedFeeLowIncome'],
                'reducedFeeUniversalCredit' => (bool)$this->data['reducedFeeUniversalCredit'],
                ]);
        $validation = $lpa->validate(['reducedFeeLowIncome', 'reducedFeeUniversalCredit']);
        
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
