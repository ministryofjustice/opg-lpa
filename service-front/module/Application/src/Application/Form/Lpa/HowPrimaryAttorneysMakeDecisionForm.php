<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class HowPrimaryAttorneysMakeDecisionForm extends AbstractForm
{
    protected $formElements = [
            'how' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    'jointly-attorney-severally' => [
                                            'label' => 'Jointly and severally', 
                                            'value' => 'jointly-attorney-severally', 
                                    ],
                                    'jointly' => [
                                            'label' => 'Jointly',
                                            'value' => 'jointly',
                                    ],
                                    'depends' => [
                                            'label' => 'Jointly for some decisions, and jointly and severally for other decisions',
                                            'value' => 'depends',
                                    ],
                            ],
                    ],
            ],
            'howDetails' => [
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
    
    public function __construct ($formName = 'primary-attorney-decisions')
    {
        
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        $decision = new PrimaryAttorneyDecisions($this->modelization($this->data));
        
        $validation = $decision->validate(['how']);
        
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
