<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class WhenReplacementAttorneyStepInForm extends AbstractForm
{
    protected $formElements = [
            'when' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    'first' => [
                                            'label' => 'As soon as one of the original attorneys can no longer act',
                                            'value' => 'first',
                                    ],
                                    'last' => [
                                            'label' => 'Only when none of the original attorneys can act',
                                            'value' => 'last',
                                    ],
                                    'depends' => [
                                            'label' => 'In some other way...',
                                            'value' => 'depends'
                                    ],
                            ],
                    ],
            ],
            'whenDetails' => [
                    'type' => 'TextArea',
                    'options' => [
                            'label' => '',
                    ],
                    'attributes' => [
                            'rows' => 24,
                            'cols' => 80,
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
                    'attributes' => [
                            'value' => 'Save and continue'
                    ],
                    
            ],
    ];
    
    public function __construct ($formName = 'when-replacement-attonrey-step-in')
    {
        
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        $document = new ReplacementAttorneyDecisions($this->modelization($this->data));
        
        $validation = $document->validate(['when']);
        
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
