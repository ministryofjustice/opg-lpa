<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;

class CorrespondentSwitcherForm extends AbstractForm
{
    protected $lpa;
    
    protected $formElements = [
            'switch-to-type' => [
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
        if(array_key_exists('lpa', $options)) {
            $this->lpa  = $options['lpa'];
            $this->user = $options['user'];
            unset($options['lpa'], $options['user']);
        }
    
        parent::__construct($name, $options);
    }
    
    public function init ()
    {
        $this->formElements['switch-to-type']['options']['value_options'] = [
                'me'    => (string)$this->user->name . ' (Myself)',
                'donor' => (string)$this->lpa->document->donor->name . ' (The donor)',
        ];
        
        foreach($this->lpa->document->primaryAttorneys as $attorney) {
            $this->formElements['switch-to-type']['options']['value_options'][$attorney->id] = (($attorney instanceof Human)?(string)$attorney->name:$attorney->name). ' (Attorney)';
        }
        
        $this->formElements['switch-to-type']['options']['value_options']['other'] = 'Other';
        
        $this->setName('form-correspondent-selector');
        
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
