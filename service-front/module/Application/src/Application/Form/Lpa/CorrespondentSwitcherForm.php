<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
class CorrespondentSwitcherForm extends AbstractForm
{
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
    
    public function __construct (Lpa $lpa, $formName = 'correspondent-selector')
    {
        $this->lpa = $lpa;
        
        $this->formElements['switch-to-type']['options']['value_options'] = [
                'me'    => 'Myself',
                'donor' => $lpa->document->donor->name->__toString() . ' (The donor)',
        ];
        
        foreach($lpa->document->primaryAttorneys as $attorney) {
            $this->formElements['switch-to-type']['options']['value_options'][$attorney->id] = (($attorney instanceof Human)?$attorney->name->__toString():$attorney->name). ' (Attorney)';
        }
        
        $this->formElements['switch-to-type']['options']['value_options']['other'] = 'Other';
        
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
