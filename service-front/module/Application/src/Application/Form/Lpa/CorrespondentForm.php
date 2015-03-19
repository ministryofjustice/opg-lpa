<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Correspondence;

class CorrespondentForm extends AbstractActorForm
{
    protected $formElements = [
            'who'        => [
                    'type' => 'Hidden',
            ],
            'name-title' => [
                    'type' => 'Text',
            ],
            'name-first' => [
                    'type' => 'Text',
            ],
            'name-last' => [
                    'type' => 'Text',
            ],
            'company' => [
                    'type' => 'Text',
            ],
            'email-address' => [
                    'type' => 'Email',
            ],
            'phone-number' => [
                    'type' => 'Text',
            ],
            'address-address1' => [
                    'type' => 'Text',
            ],
            'address-address2' => [
                    'type' => 'Text',
            ],
            'address-address3' => [
                    'type' => 'Text',
            ],
            'address-postcode' => [
                    'type' => 'Text',
            ],
            'contactByPost' => [
                    'type' => 'Checkbox',
                    'options' => [
                            'checked_value' => true,
                            'unchecked_value' => false,
                    ],
            ],
            'contactInWelsh' => [
                    'type' => 'Checkbox',
                    'options' => [
                            'checked_value' => true,
                            'unchecked_value' => false,
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function __construct ($formName = 'correspondent')
    {
        parent::__construct($formName);
        
    }
    
    public function validateByModel()
    {
        $this->actorModel = new Correspondence();
        
        return parent::validateByModel();
    }
}
