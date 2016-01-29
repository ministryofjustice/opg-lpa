<?php
namespace Application\Form\User;

use Zend\Validator;

/**
 * For to request a password reset email be sent out.
 *
 * Class ResetPasswordEmail
 * @package Application\Form\User
 */
class SetPassword extends AbstractForm {

    public function __construct( $formName = 'set-password' ){

        parent::__construct( $formName );

        //---

        $this->add(array(
            'name' => 'password',
            'type' => 'Password',
        ));

        $this->add(array(
            'name' => 'password_confirm',
            'type' => 'Password',
        ));

        //--------------------------------
        $this->setUseInputFilterDefaults(false);
        
        $inputFilter = $this->getInputFilter();

        $inputFilter->add(array(
            'name'     => 'password',
            'required' => true,
            'validators' => array(
                array(
                    'name'    => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options' => array(
                        'messages' => [
                            Validator\NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ),
                ),
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'min'      => 8,
                        'messages' => [
                            Validator\StringLength::TOO_SHORT => 'min-length-%min%',
                        ],
                    ),
                ),
                array(
                    'name'    => 'Regex',
                    'options' => array(
                        'pattern' => '/.*[0-9].*/',
                        'messages' => [
                            Validator\Regex::NOT_MATCH => 'must-include-digit',
                        ],
                    ),
                ),
                array(
                    'name'    => 'Regex',
                    'options' => array(
                        'pattern' => '/.*[a-z].*/',
                        'messages' => [
                            Validator\Regex::NOT_MATCH => 'must-include-lower-case',
                        ],
                    ),
                ),
                array(
                    'name'    => 'Regex',
                    'options' => array(
                        'pattern' => '/.*[A-Z].*/',
                        'messages' => [
                            Validator\Regex::NOT_MATCH => 'must-include-upper-case',
                        ],
                    ),
                ),
            ),
        )); // add

        $inputFilter->add(array(
            'name'     => 'password_confirm',
            'required' => true,
            'validators' => array(
                array(
                    'name'    => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options' => array(
                        'messages' => [
                            Validator\NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ),
                ),
                array(
                    'name'    => 'Identical',
                    'break_chain_on_failure' => true,
                    'options' => array(
                        'token' => 'password',
                        'messages' => [
                            Validator\Identical::NOT_SAME => 'did-not-match',
                        ],
                    ),
                ),
            ),
        )); // add

        //---

        $this->setInputFilter( $inputFilter );

    } // function

} // class
