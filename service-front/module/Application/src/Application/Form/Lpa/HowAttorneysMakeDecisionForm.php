<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class HowAttorneysMakeDecisionForm extends AbstractForm
{
    protected $formElements = [
            'how' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'required' => true,
                    'options' => [
                            'value_options' => [
                                    'jointly-attorney-severally' => [
                                            'value' => 'jointly-attorney-severally', 
                                    ],
                                    'jointly' => [
                                            'value' => 'jointly',
                                    ],
                                    'depends' => [
                                            'value' => 'depends',
                                    ],
                            ],
                    ],
            ],
            'howDetails' => [
                    'required' => true,
                    'type' => 'TextArea',
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function init ()
    {
        $this->setName('form-primary-attorney-decisions');
        
        parent::init();
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $decision = new PrimaryAttorneyDecisions($this->data);
        
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
