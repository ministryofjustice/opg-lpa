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
    
    public function __construct ($formName = 'correspondent')
    {
        parent::__construct($formName);
        
    }
    
    public function validateByModel()
    {
        $this->actor = new Correspondence();
        
        return parent::validateByModel();
    }
}
