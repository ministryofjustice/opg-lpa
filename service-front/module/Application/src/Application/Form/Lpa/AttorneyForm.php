<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Zend\Validator;

class AttorneyForm extends AbstractActorForm
{
    protected $formElements;    
    public function __construct ()
    {
        $this->formElements  = [
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
                    'type' => 'Email',
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
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
        parent::__construct('attorney');
        
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $this->actorModel = new Human();
        
        return parent::validateByModel();
    }
}
