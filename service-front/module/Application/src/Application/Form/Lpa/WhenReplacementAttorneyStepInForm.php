<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class WhenReplacementAttorneyStepInForm extends AbstractForm
{
    protected $formElements = [
            'when' => [
                'type'      => 'Zend\Form\Element\Radio',
                'required'  => true,
                'options'   => [
                    'value_options' => [
                        'first'  => [
                                'value' => 'first',
                        ],
                        'last'   => [
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
                    'required' => true,
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function init ()
    {
        $this->setName('form-when-replacement-attonrey-step-in');
        
        parent::init();
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $document = new ReplacementAttorneyDecisions($this->data);
        
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
