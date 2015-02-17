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
                                'value' => 'first',
                        ],
                        'last' => [
                                'value' => 'last',
                        ],
                        'depends' => [
                                'value' => 'depends'
                        ],
                    ],
                ],
            ],
            'whenDetails' => [
                    'type' => 'TextArea',
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
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
