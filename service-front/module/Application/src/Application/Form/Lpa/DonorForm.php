<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Donor;

class DonorForm extends AbstractForm
{
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
            'otherNames' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Other names'
                    ],
            ],
            'dob-date' => [
                    'type' => 'Date',
                    'options' => [
                            'label' => 'Date of birth'
                    ],
            ],
            'email-address' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Email address'
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
            'canSign' => [
                    'type' => 'CheckBox',
                    'options' => [
                            'label' => 'Donor is able to sign on the form'
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
                    'attributes' => [
                            'value' => 'Save and continue'
                    ],
                    
            ],
    ];
    
    public function __construct ($formName = 'donor')
    {
        
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        $modelizedData = $this->unflattenForModel($this->data);
        
        if($modelizedData['dob']['date'] == "") {
            $modelizedData['dob'] = null;
        }
        
        if($modelizedData['email']['address'] == "") {
            $modelizedData['email'] = null;
        }
        
        $donor = new Donor($modelizedData);
        
        $validation = $donor->validate();
        
        if(($modelizedData['dob'] == null) && array_key_exists('dob', $validation)) {
            $validation['dob-date'] = $validation['dob'];
            unset($validation['dob']);
        }
        
        if(($modelizedData['email'] == null) && array_key_exists('email', $validation)) {
            $validation['email-address'] = $validation['email'];
            unset($validation['email']);
        }
        
        if(count($validation) == 0) {
            return ['isValid'=>true, 'messages' => []];
        }
        else {
            return [
                    'isValid'=>false,
                    'messages' => $this->modelValidationMessageConverter($validation),
            ];
        }
    }
}
