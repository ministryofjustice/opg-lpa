<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
class CertificateProviderForm extends AbstractActorForm
{
    protected $formElements = [
            'name-title' => [
                    'type' => 'Text',
            ],
            'name-first' => [
                    'type' => 'Text',
            ],
            'name-last' => [
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
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function __construct ($formName = 'certificate-provider')
    {
        
        parent::__construct($formName);
        
    }
    
    public function validateByModel()
    {
        $this->actorModel = new CertificateProvider();
        
        return parent::validateByModel();
    }
}
