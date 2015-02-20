<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
class WhoAreYouForm extends AbstractForm
{
    protected $formElements = [
            'when' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    'donor' => [
                                            'value' => 'donor',
                                    ],
                                    'friendOrFamily' => [
                                            'value' => 'friendOrFamily',
                                    ],
                                    'professional'   => [
                                            'value' => 'professional',
                                    ],
                                    'solicitor'      => [
                                            'value' => 'solicitor',
                                    ],
                                    'will-writer'      => [
                                            'value' => 'will-writer',
                                    ],
                                    'other'      => [
                                            'value' => 'other',
                                    ],
                                    'digitalPartner'      => [
                                            'value' => 'digitalPartner',
                                    ],
                                    'Age-Uk'      => [
                                            'value' => 'Age-Uk',
                                    ],
                                    'Alzheimer-Society'      => [
                                            'value' => 'Alzheimer-Society',
                                    ],
                                    'Citizens-Advice-Bureau'      => [
                                            'value' => 'Citizens-Advice-Bureau',
                                    ],
                                    'organisation'  => [
                                            'value' => 'organisation',
                                    ],
                                    'notSaid'  => [
                                            'value' => 'notSaid'
                                    ],
                            ],
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function __construct ($formName = 'who-are-you')
    {
        
        parent::__construct($formName);
        
    }
    
    public function validateByModel()
    {
        $decisions = new WhoAreYou($this->data);
        
        $validation = $decisions->validate();
        
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
