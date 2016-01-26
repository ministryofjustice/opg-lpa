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

        $this->setUseInputFilterDefaults(false);
        
        $inputFilter = $this->getInputFilter();

        $inputFilter->add(array(
            'name'     => 'email',
            'required' => true,
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
                array('name' => 'StringToLower'),
            ),
            'validators' => array(
                array(
                    'name'    => 'EmailAddress',
                    /* We'll just use the ZF2 messages for these - there are lots of them 
                     * and they include such classics as:
                     *
                     * "'%hostname%' is not in a routable network segment.
                     * The email address should not be resolved from public network"
                     */
                ),
            ),
            array(
                'name'    => 'NotEmpty',
                'options' => array(
                    'messages' => [
                        Validator\NotEmpty::IS_EMPTY => 'cannot-be-empty',
                    ],
                ),
            ),
        ));

        $inputFilter->add(array(
            'name'     => 'email_confirm',
            'required' => true,
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
                            Validator\Identical::NOT_SAME => 'did-not-match',
                        ],
                    ),
                ),
                array(
                    'name'    => 'NotEmpty',
                    'options' => array(
                        'messages' => [
                            Validator\NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ),
                ),
                
            ),
        ));

        $inputFilter->add(array(
            'name'     => 'terms',
            'required' => true,
            'error_message' => 'must-be-checked',
            'validators' => array(
                array(
                    'name'    => 'Identical',
                    'options' => array(
                        'token' => '1',
                        'literal' => true,
                        'messages' => [
                            Validator\Identical::NOT_SAME => 'must-be-checked',
                        ],
                    ),
                ),
            ),
        ));

        //---

        $this->setInputFilter( $inputFilter );

    } // function

} // class
