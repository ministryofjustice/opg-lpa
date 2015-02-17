<?php
namespace Application\Form\Lpa;

class AttorneyForm extends AbstractForm
{
    use \Application\Form\Lpa\Traits\ActorFormModelization;
    
    protected $formElements = [
            'name-title' => [
                    'type' => 'Zend\Form\Element\Select',
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
                    'type' => 'Date',
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
    
    public function __construct ($formName = 'primary-attorney')
    {
        
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        
        return $this->validateModel('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        
    }
}
