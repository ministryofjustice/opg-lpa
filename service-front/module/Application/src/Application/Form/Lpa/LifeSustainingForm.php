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
                                    '1' => [
                                            'label' => "Option A: Yes. I want to give my attorneys authority to give or refuse consent to life-sustaining treatment on my behalf",
                                            'value' => '1',
                                    ],
                                    '0' => [
                                            'label' => "Option B: No. I don’t want to give my attorneys authority to give or refuse consent to life-sustaining treatment on my behalf",
                                            'value' => '0',
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
