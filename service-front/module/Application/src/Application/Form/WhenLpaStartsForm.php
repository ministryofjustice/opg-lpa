<?php
namespace Application\Form;

use Zend\InputFilter\InputFilterAwareInterface;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class WhenLpaStartsForm extends AbstractForm implements InputFilterAwareInterface
{
    protected $formElements = [
            'whenLpaStarts' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW         => "as soon as it's registered (with my consent)",
                                    PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY => "only if I don't have mental capacity",
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
            $formName = 'whenLpaStarts';
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
