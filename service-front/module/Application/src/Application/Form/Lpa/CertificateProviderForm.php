<?php
namespace Application\Form\Lpa;

class CertificateProviderForm extends AbstractForm
{
    use \Application\Form\Lpa\Traits\ActorFormModelization;
    
    protected $formElements = [
            'name-title' => [
                    'type' => 'Zend\Form\Element\Select',
                    'options' => [
                            'label' => 'Title',
                            'empty_option' => 'Please choose your title',
                            'value_options' => [
                                    'Mr'   => 'Mr',
                                    'Mrs'  => 'Mrs',
                                    'Ms'   => 'Ms',
                                    'Miss' => 'Miss',
                                    'Sir'  => 'Sir'
                            ],
                    ],
            ],
            'name-first' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'First names'
                    ],
            ],
            'name-last' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Last name'
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
                            'value' => 'Save and continue'
                    ],
                    
            ],
    ];
    
    public function __construct ($formName = 'certificate-provider')
    {
        
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        
        return $this->validateModel('\Opg\Lpa\DataModel\Lpa\Document\CertificateProvider');
        
    }
}
