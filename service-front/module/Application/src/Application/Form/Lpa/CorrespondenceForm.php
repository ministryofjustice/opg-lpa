<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Elements\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Elements\PhoneNumber;

class CorrespondenceForm extends AbstractActorForm
{
    private $lpa;
    
    public function __construct($name, $options)
    {
        if(array_key_exists('lpa', $options)) {
            $this->lpa = $options['lpa'];
            unset($options['lpa']);
        }
    
        parent::__construct($name, $options);
    }
    
    public function init ()
    {
        $this->setName('form-correspondence');
        $this->formElements = [
                'correspondence' => [
                        'type' => 'Application\Form\Lpa\CorrespondenceFieldset',
                        'options' => [
                                'checked_value' => true,
                                'unchecked_value' => false,
                        ],
                        'validators' => [
                            [
                                'name' => 'Application\Form\Validator\Correspondence',
                            ]
                        ],
                ],
                'submit' => [
                        'type' => 'Zend\Form\Element\Submit',
                ],
        ];        
        parent::init();
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $error = ['correspondence' => []];

        $correspondent = new Correspondence([
            'contactByPost' => (bool)$this->data['correspondence']['contactByPost'],
            'contactByInWelsh' => (bool)$this->data['correspondence']['contactInWelsh'],
        ]);

        
        if($this->data['correspondence']['contactByEmail'] == "1") {
            if(  !isset($this->data['correspondence']['email-address']) ) {
                $error['correspondence']['contactByEmail'] = ["Email address is not provided"];
            } else {
                $correspondent->email = [ 'address' => $this->data['correspondence']['email-address'] ];
            }
        }



        if($this->data['correspondence']['contactByPhone'] == "1") {
            if( !isset( $this->data['correspondence']['phone-number']) ) {
                $error['correspondence']['contactByPhone'] = ["Phone number is not provided"];
            } else {
                $correspondent->phone = [ 'number' => $this->data['correspondence']['phone-number'] ];
            }
        }

        
        $modelValidation = $correspondent->validate(['contactByPost', 'contactInWelsh', 'email', 'phone']);

        if(count($modelValidation) == 0) {
            if(count($error['correspondence']) == 0) {
                return ['isValid'=>true, 'messages' => []];
            }
            else {
                return [
                        'isValid'=>false,
                        'messages' => $error,
                ];
            }
        }
        else {

            $errors = $this->modelValidationMessageConverter($modelValidation);

            return [
                    'isValid'=>false,
                    'messages' => array_merge( $error, ['correspondence' => $errors] ),
            ];
        }
    }
    
}
