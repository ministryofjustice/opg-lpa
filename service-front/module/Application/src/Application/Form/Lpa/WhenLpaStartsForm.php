<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class WhenLpaStartsForm extends AbstractForm
{
    protected $formElements = [
            'when' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    'now' => [
                                            'value' => 'now',
                                    ],
                                    'no-capacity' => [
                                            'value' => 'no-capacity',
                                    ],
                            ],
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function __construct ($formName = 'whenLpaStarts')
    {
        
        parent::__construct($formName);
        
    }
    
    public function validateByModel()
    {
        $decisions = new PrimaryAttorneyDecisions($this->data);
        
        $validation = $decisions->validate(['when']);
        
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
