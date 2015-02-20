<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;
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
            $this->formElements['switch-to-type']['options']['value_options'][$attorney->id] = $attorney->name->__toString(). ' (Attorney)';
        }
        
        $this->formElements['switch-to-type']['options']['value_options']['other'] = 'Other';
        
        parent::__construct($formName);
    }
    
    public function validateByModel()
    {
        return ['isValid'=>true, 'messages' => []];
    }
}
