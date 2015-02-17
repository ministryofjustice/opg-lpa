<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class LifeSustainingForm extends AbstractForm
{
    protected $formElements = [
            'canSustainLife' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    true => [
                                            'value' => true,
                                    ],
                                    false => [
                                            'value' => false,
                                    ],
                            ],
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
                    'attributes' => [
                            'value' => 'Save and continue'
                    ],
                    
            ],
    ];
        
    public function __construct ($formName = 'lifeSustaining')
    {
        
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        $decisions = new PrimaryAttorneyDecisions($this->modelization($this->data));
        
        $validation = $decisions->validate(['canSustainLife']);
        
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
