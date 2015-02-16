<?php
namespace Application\Form\Lpa;

class TrustCorporationForm extends AbstractForm
{
    use \Application\Form\Lpa\Traits\ActorFormModelization;
    
    protected $formElements = [
            'name' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Company names'
                    ],
            ],
            'number' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Registration No.'
                    ],
            ],
            'email-address' => [
                    'type' => 'Email',
                    'options' => [
                            'label' => 'Email (optional)'
                    ],
            ],
            'address-address1' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Address line 1'
                    ],
            ],
            'address-address2' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Address line 2'
                    ],
            ],
            'address-address3' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Address line 3'
                    ],
            ],
            'address-postcode' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Postcode'
                    ],
                    
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
                    'attributes' => [
                            'value' => 'Save details'
                    ],
                    
            ],
    ];
    
    public function __construct ($formName = 'donor')
    {
        
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        
        return $this->validateModel('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation');
        
    }
}
