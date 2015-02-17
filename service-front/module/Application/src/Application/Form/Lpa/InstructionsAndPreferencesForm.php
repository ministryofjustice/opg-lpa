<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Document;
class InstructionsAndPreferencesForm extends AbstractForm
{
    protected $formElements = [
            'instruction' => [
                    'type' => 'Textarea',
            ],
            'preference' => [
                    'type' => 'Textarea',
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function __construct ($formName = 'instructions-and-preferences')
    {
        
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        $document = new Document($this->modelization($this->data));
        
        $validation = $document->validate(['instructions, preferences']);
        
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
