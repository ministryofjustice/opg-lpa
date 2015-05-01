<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Payment\Payment;

class BenefitsForm extends AbstractForm
{
    protected $formElements = [
            'reducedFeeReceivesBenefits' => [
                    'type'      => 'Zend\Form\Element\Radio',
                    'required'  => true,
                    'options'   => [
                            'value_options' => [
                                    'no' => [
                                            'value' => 0,
                                    ],
                                    'yes' => [
                                            'value' => 1,
                                    ],
                            ],
                    ],
            ],
            'reducedFeeAwardedDamages' => [
                    'type'      => 'Zend\Form\Element\Radio',
                    'required'  => true,
                    'options'   => [
                            'value_options' => [
                                    'no-damage' => [
                                            'value' => 1,
                                    ],
                                    'less-than-16k' => [
                                            'value' => 1,
                                    ],
                                    'over-16k' => [
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
        $this->setName('benefits');
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
                'reducedFeeReceivesBenefits' => (bool)$this->data['reducedFeeReceivesBenefits'],
                'reducedFeeAwardedDamages'   => array_key_exists('reducedFeeAwardedDamages', $this->data)?(bool)$this->data['reducedFeeAwardedDamages']:null,
                ]);
        $validation = $lpa->validate(['reducedFeeReceivesBenefits', 'reducedFeeAwardedDamages']);
        
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
