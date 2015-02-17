<?php
namespace Application\Form\Lpa;

class TrustCorporationForm extends AbstractForm
{
    use \Application\Form\Lpa\Traits\ActorFormModelization;
    
    protected $formElements = [
            'name' => [
                    'type' => 'Text',
            ],
            'number' => [
                    'type' => 'Text',
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
    
    public function __construct ($formName = 'donor')
    {
        
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        
        return $this->validateModel('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation');
        
    }
}
