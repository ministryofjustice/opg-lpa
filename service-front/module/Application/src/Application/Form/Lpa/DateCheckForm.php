<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Validator\NotEmpty;
use Zend\Validator\StringLength;

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
    
    public function __construct (Lpa $lpa, $formName = 'type-form')
    {
        $numAttorneys = count($lpa->get('document')->get('primaryAttorneys'));
        
        for ($i=0; $i<$numAttorneys; $i++) {
            $this->formElements['sign-date-attorney-' . $i] = [];
        }

        foreach ($this->formElements as $key => &$element) {
            if ($key != 'submit') {
                $element['type'] = 'Zend\Form\Element';
                $element['required'] = true;
                $element['validators'] = $this->dateValidators;
            }
        }
        
        parent::__construct($formName);
        
    }
    
   /**
    * Validate form input data through model validators.
    */
    public function validateByModel()
    {
        return ['isValid' => true];
    }

}
