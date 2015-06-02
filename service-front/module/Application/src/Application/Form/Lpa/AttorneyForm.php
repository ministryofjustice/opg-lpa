<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;

class AttorneyForm extends AbstractActorForm
{
    protected $formElements;
        
    public function init ()
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
            'dob-date' => [
                    'type' => 'Application\Form\Fieldset\Dob',
                    'validators' => [
                        [
                            'name'    => 'Application\Form\Validator\Date',
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
    
        $this->setName('attorney');
        
        parent::init();
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
