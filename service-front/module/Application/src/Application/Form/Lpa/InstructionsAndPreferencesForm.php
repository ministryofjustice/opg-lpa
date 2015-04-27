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
    
    public function init ()
    {
        $this->setName('instructions-and-preferences');
        
        parent::init();
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $document = new Document($this->data);
        
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
