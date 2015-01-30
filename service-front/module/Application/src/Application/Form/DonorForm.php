<?php
namespace Application\Form;

use Zend\InputFilter\InputFilterAwareInterface;
use Opg\Lpa\DataModel\Lpa\Document\Donor;

class DonorForm extends AbstractForm implements InputFilterAwareInterface
{
    protected $inputFilter;
    
    protected $formElements = [
            'name-title' => [
                    'type' => 'Zend\Form\Element\Select',
                    'options' => [
                            'label' => 'Title',
                            'empty_option' => 'Please choose your title',
                            'value_options' => [
                                    '0'=>'Mr',
                                    '1'=>'Mrs',
                                    '2'=>'Ms',
                                    '3'=>'Miss',
                                    '4'=>'Sir'
                            ],
                    ],
                    'attributes' => [
                            'class' => 'form-element form-select',
                    ],
            ],
            'name-first' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'First names'
                    ],
                    'attributes' => [
                            'class' => 'form-element form-text',
                    ],
            ],
            'name-last' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Last name'
                    ],
                    'attributes' => [
                            'class' => 'form-element form-text',
                    ],
            ],
            'otherNames' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Other names'
                    ],
                    'attributes' => [
                            'class' => 'form-element form-text',
                    ],
            ],
            'dob-date' => [
                    'type' => 'Date',
                    'options' => [
                            'label' => 'Date of birth'
                    ],
                    'attributes' => [
                            'id' => 'dob',
                            'class' => 'form-element form-text calendar',
                    ],
            ],
            'email-address' => [
                    'type' => 'Email',
                    'options' => [
                            'label' => 'Email address'
                    ],
                    'attributes' => [
                            'class' => 'form-element form-text',
                            'placeholder' => 'example@email.com',
                    ],
            ],
            'address-address1' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Address line 1'
                    ],
                    'attributes' => [
                            'class' => 'form-element form-text',
                    ],
                    
            ],
            'address-address2' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Address line 2'
                    ],
                    'attributes' => [
                            'class' => 'form-element form-text',
                    ],
                    
            ],
            'address-address3' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Address line 3'
                    ],
                    'attributes' => [
                            'class' => 'form-element form-text',
                    ],
                    
            ],
            'address-postcode' => [
                    'type' => 'Text',
                    'options' => [
                            'label' => 'Postcode'
                    ],
                    'attributes' => [
                            'class' => 'form-element form-text',
                    ],
                    
            ],
            'canSign' => [
                    'type' => 'CheckBox',
                    'options' => [
                            'label' => 'Donor is able to sign on the form'
                    ],
                    'attributes' => [
                            'class' => 'form-element form-checkbox',
                    ],
                    
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
                    'attributes' => [
                            'id' => 'submit-button',
                            'class' => ' form-element form-button',
                            'value' => 'Submit'
                    ],
                    
            ],
    ];
    
    public function __construct ($formName = null)
    {
        if($formName == null) {
            $formName = 'donor';
        }
        
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        $donor = new Donor($this->unflattenForModel($this->data));
        
        $validation = $donor->validate();
        
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
