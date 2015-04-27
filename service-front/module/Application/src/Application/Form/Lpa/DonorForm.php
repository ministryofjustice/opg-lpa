<?php
namespace Application\Form\Lpa;

use Zend\Validator;
use Opg\Lpa\DataModel\Lpa\Document\Donor;

class DonorForm extends AbstractActorForm
{
    protected $formElements;
    
    public function init ()
    {
        $this->formElements = [
            'name-title' => [
                    'type' => 'Text',
            ],
            'name-first' => [
                    'type' => 'Text',
            ],
            'name-last' => [
                    'type' => 'Text',
            ],
            'otherNames' => [
                    'type' => 'Text',
            ],
            'dob-date-day' => [
                    'type' => 'Text',
                    'required' => true,
                    'filters' => [
                            ['name' => 'Zend\Filter\Int'],
                    ],
                    'validators' => [
                        [
                            'name'    => 'Between',
                            'options' => [
                                'min' => 1, 'max' => 31,
                                'messages' => [
                                    Validator\Between::NOT_BETWEEN => "must be between %min% and %max%",
                                ],
                            ],
                        ],
                    ],
            ],
            'dob-date-month' => [
                    'type' => 'Text',
                    'required' => true,
                    'filters' => [
                            ['name' => 'Zend\Filter\Int'],
                    ],
                    'validators' => [
                        [
                            'name'    => 'Between',
                            'options' => [
                                'min' => 1, 'max' => 12,
                                'messages' => [
                                    Validator\Between::NOT_BETWEEN => "must be between %min% and %max%",
                                ],
                            ],
                        ],
                    ],
            ],
            'dob-date-year' => [
                    'type' => 'Text',
                    'required' => true,
                    'filters' => [
                            ['name' => 'Zend\Filter\Int'],
                    ],
                    'validators' => [
                        [
                            'name'    => 'Between',
                            'options' => [
                                'min' => (int)date('Y') - 150, 'max' => (int)date('Y'),
                                'messages' => [
                                    Validator\Between::NOT_BETWEEN => "must be between %min% and %max%",
                                ],
                            ],
                        ],
                    ],
            ],
            'email-address' => [
                    'type' => 'Text',
                    'validators' => [
                        [
                            'name'    => 'EmailAddress',
                        ],
                    ],
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
            'canSign' => [
                    'type' => 'CheckBox',
                    'options' => [
                            'checked_value' => false,
                            'unchecked_value' => true,
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
                    
            ],
        ];
        
        $this->setName('donor');
        
        parent::init();
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $this->actorModel = new Donor();
        
        return parent::validateByModel();
    }
}
