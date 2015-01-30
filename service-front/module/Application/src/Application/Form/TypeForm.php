<?php
namespace Application\Form;

use Zend\InputFilter\InputFilterAwareInterface;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\Document;

class TypeForm extends AbstractForm implements InputFilterAwareInterface
{
    protected $inputFilter;
    
    protected $formElements = [
            'lpaType' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    'pf'=>'Property and financial affairs',
                                    'hw'=>'Health and welfare',
                            ],
                    ],
                    'attributes' => [
                            'class' => 'form-element form-radio',
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
                    'attributes' => [
                            'id' => 'submit-button',
                            'class' => ' form-element form-button',
                            'value' => 'Save and continue'
                    ],
                    
            ],
    ];
    
    public function __construct ($formName = null)
    {
        if($formName == null) {
            $formName = 'type';
        }
        
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        printr($this->data);
        
        $this->data['type'] = '';
        $document = new Document($this->unflattenForModel($this->data));
        
        $validation = $document->validate();
        printr($validation);
        
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
