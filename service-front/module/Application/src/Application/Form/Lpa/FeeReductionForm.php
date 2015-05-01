<?php
namespace Application\Form\Lpa;

class FeeReductionForm extends AbstractForm
{
    protected $formElements = [
            'applyForFeeReduction' => [
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
        $this->setName('fee-reduction');
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
