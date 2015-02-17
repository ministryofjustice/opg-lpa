<?php
namespace Application\Form\Lpa;

class DonorForm extends AbstractForm
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
    
    public function __construct ($formName = 'donor')
    {
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        
        return $this->validateModel('\Opg\Lpa\DataModel\Lpa\Document\Donor');
        
    }
    
}
