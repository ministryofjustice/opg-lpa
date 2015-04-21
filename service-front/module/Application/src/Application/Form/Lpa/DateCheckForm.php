<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;

class DateCheckForm extends AbstractForm
{
    
    protected $formElements = [
        'sign-date-donor' => [],
        'sign-date-certificate-provider' => [],
        'submit' => [
            'type' => 'Zend\Form\Element\Submit',
        ],
    ];
    
    private $dateValidators = [
        [
            'name' => 'Date',
            'options' => [
                'format' => 'd/m/Y',
            ],
        ],
        [
            'name' => 'StringLength',
            'options' => [
                'min' => 10,
                'max' => 10,
            ],
        ]
    ];
    
    public function __construct (Lpa $lpa)
    {
        foreach($lpa->document->primaryAttorneys as $idx => $attorney) {
            $this->formElements['sign-date-attorney-' . $idx] = [];
        }
        
        foreach($lpa->document->replacementAttorneys as $idx => $attorney) {
            $this->formElements['sign-date-replacement-attorney-' . $idx] = [];
        }
        
        foreach ($this->formElements as $key => &$element) {
            if ($key != 'submit') {
                $element['type'] = 'Zend\Form\Element';
                $element['required'] = true;
                $element['validators'] = $this->dateValidators;
            }
        }
        
        parent::__construct('date-checker');
        
    }
    
   /**
    * Validate form input data through model validators.
    */
    public function validateByModel()
    {
        return ['isValid' => true];
    }

}
