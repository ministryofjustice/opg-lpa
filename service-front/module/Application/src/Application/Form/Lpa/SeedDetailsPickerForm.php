<?php
namespace Application\Form\Lpa;

class SeedDetailsPickerForm extends AbstractForm
{
    private $seedDetails;
    
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
    
    public function __construct($name, $options)
    {
        if(array_key_exists('seedDetails', $options)) {
            $this->seedDetails = $options['seedDetails'];
            unset($options['seedDetails']);
        }
        
        parent::__construct($name, $options);
    }
    
    public function init ()
    {
        foreach($this->seedDetails as $idx=>$actor) {
            $this->formElements['pick-details']['options']['value_options'][$idx] = $actor['label'];
        }
        
        $this->setName('form-seed-details-picker');
        
        parent::init();
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
