<?php
namespace Application\Form\Lpa;

class FeeReductionForm extends AbstractForm
{
    protected $formElements = [
            'reductionOptions' => [
                    'type'      => 'Zend\Form\Element\Radio',
                    'required'  => true,
                    'options'   => [
                            'value_options' => [
                                    'reducedFeeReceivesBenefits' => [
                                            'value' => 'reducedFeeReceivesBenefits',
                                    ],
                                    'reducedFeeUniversalCredit' => [
                                            'value' => 'reducedFeeUniversalCredit',
                                    ],
                                    'reducedFeeLowIncome' =>[
                                            'value' => 'reducedFeeLowIncome',
                                    ],
                                    'notApply' => [
                                            'value' => 'notApply'
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
        $this->setName('form-fee-reduction');
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
