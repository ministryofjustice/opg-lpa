<?php
namespace Application\Form;

use Zend\InputFilter\InputFilterAwareInterface;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class LifeSustainingForm extends AbstractForm implements InputFilterAwareInterface
{
    protected $formElements = [
            'whenLpaStarts' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    true => "Option A: Yes. I want to give my attorneys authority to give or refuse consent to life-sustaining treatment on my behalf",
                                    false => "Option B: No. I don’t want to give my attorneys authority to give or refuse consent to life-sustaining treatment on my behalf",
                            ],
                    ],
                    'attributes' => [
                            'class' => 'form-element form-radio',
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
                    'attributes' => [
                            'id' => 'submit-button',
                            'class' => ' form-element form-button',
                            'value' => 'Submit'
                    ],
                    
            ],
    ];
        
    public function __construct ($formName = null)
    {
        if($formName == null) {
            $formName = 'lifeSustaining';
        }
        
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
