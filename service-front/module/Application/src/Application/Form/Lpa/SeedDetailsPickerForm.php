<?php
namespace Application\Form\Lpa;

class SeedDetailsPickerForm extends AbstractForm
{
    protected $formElements = [
            'pick-details' => [
                    'type' => 'Zend\Form\Element\Select',
                    'required' => true,
                    'options' => [
                            'value_options' => [],
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function __construct ($seedDetails, $formName = 'seed-details-picker')
    {
        foreach($seedDetails as $idx=>$actor) {
            $this->formElements['pick-details']['options']['value_options'][$idx] = $actor['label'];
        }
        
        parent::__construct($formName);
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        return ['isValid'=>true, 'messages' => []];
    }
}
