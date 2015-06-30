<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;

class RepeatApplicationForm extends AbstractForm
{
    protected $formElements = [
            'isRepeatApplication' => [
                    'type'      => 'Zend\Form\Element\Radio',
                    'required'  => true,
                    'options'   => [
                            'value_options' => [
                                    'is-repeat' => [
                                            'value' => 'is-repeat',
                                    ],
                                    'is-new' => [
                                            'value' => 'is-new',
                                    ],
                            ],
                    ],
            ],
            'repeatCaseNumber' => [
                    'type' => 'Text',
                    'required'  => true,
                    'validators' => [
                        [
                            'name' => 'Digits',
                        ]
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function init()
    {
        $this->setName('form-repeat-application');
        parent::init();
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        if($this->data['isRepeatApplication'] == 'is-new') {
            return ['isValid'=>true, 'messages' => []];
        }
        elseif($this->data['isRepeatApplication'] == 'is-repeat') {
            $lpa = new Lpa(['repeatCaseNumber' => (int) $this->data['repeatCaseNumber']]);
            $validation = $lpa->validate(['repeatCaseNumber']);
        
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
}
