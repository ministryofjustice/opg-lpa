<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
class TrustCorporationForm extends AbstractActorForm
{
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
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $this->actorModel = new TrustCorporation();
        
        return parent::validateByModel();
    }
}
