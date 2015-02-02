<?php
namespace Application\Form;

use Zend\InputFilter\InputFilterAwareInterface;
use Opg\Lpa\DataModel\Lpa\Document\Document;

class TypeForm extends AbstractForm implements InputFilterAwareInterface
{
    protected $formElements = [
            'type' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    Document::LPA_TYPE_PF => 'Property and financial affairs',
                                    Document::LPA_TYPE_HW => 'Health and welfare',
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
        $document = new Document($this->unflattenForModel($this->data));
        
        $validation = $document->validate();
        
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
