<?php
namespace Application\Form\User;

use Zend\Validator;

class Registration extends SetPassword {

    public function __construct( $formName = 'registration' ){

        parent::__construct($formName);

        //---

        $this->add(array(
            'name' => 'email',
            'type' => 'Email',
        ));

        $this->add(array(
            'name' => 'email_confirm',
            'type' => 'Email',
        ));

        $this->add(array(
            'name' => 'terms',
            'type' => 'Checkbox',
        ));

        //--------------------------------

        $inputFilter = $this->getInputFilter();

        $inputFilter->add(array(
            'name'     => 'email',
            'required' => true,
            'error_message' => 'You can\'t leave this box empty',
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
                array('name' => 'StringToLower'),
            ),
            'validators' => array(
                array(
                    'name'    => 'EmailAddress',
                ),
            ),
        ));

        $inputFilter->add(array(
            'name'     => 'email_confirm',
            'required' => true,
            'error_message' => 'You can\'t leave this box empty',
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
                array(
                    'name'    => 'Identical',
                    'options' => array(
                        'token' => 'email',
                        'messages' => [
                            Validator\Identical::NOT_SAME => 'must match Enter your email address',
                        ],
                    ),
                ),
            ),
        ));

        $inputFilter->add(array(
            'name'     => 'terms',
            'required' => true,
            'error_message' => 'Please tick this checkbox',
            'validators' => array(
                array(
                    'name'    => 'Identical',
                    'options' => array(
                        'token' => '1',
                        'literal' => true,
                        'messages' => [
                            Validator\Identical::NOT_SAME => 'must be checked',
                        ],
                    ),
                ),
            ),
        ));

        //---

        $this->setInputFilter( $inputFilter );

    } // function

} // class
