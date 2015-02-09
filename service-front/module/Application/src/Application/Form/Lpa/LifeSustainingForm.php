<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class LifeSustainingForm extends AbstractForm
{
    protected $formElements = [
            'lifeSustaining' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    true => "Option A: Yes. I want to give my attorneys authority to give or refuse consent to life-sustaining treatment on my behalf",
                                    false => "Option B: No. I don’t want to give my attorneys authority to give or refuse consent to life-sustaining treatment on my behalf",
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
        $decisions = new PrimaryAttorneyDecisions($this->unflattenForModel($this->data));
        
        $validation = $decisions->validate();
        
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
