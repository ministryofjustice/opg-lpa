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

        $inputFilter = $this->getInputFilter();

        $inputFilter->add(array(
            'name'     => 'password',
            'required' => true,
            'validators' => array(
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'min'      => 8,
                        'messages' => [
                            Validator\StringLength::TOO_SHORT => 'must be at least %min% characters long',
                        ],
                    ),
                ),
                array(
                    'name'    => 'Regex',
                    'options' => array(
                        'pattern' => '/.*[0-9].*/',
                        'messages' => [
                            Validator\Regex::NOT_MATCH => 'must include a number',
                        ],
                    ),
                ),
                array(
                    'name'    => 'Regex',
                    'options' => array(
                        'pattern' => '/.*[a-z].*/',
                        'messages' => [
                            Validator\Regex::NOT_MATCH => 'must include a lower-case letter',
                        ],
                    ),
                ),
                array(
                    'name'    => 'Regex',
                    'options' => array(
                        'pattern' => '/.*[A-Z].*/',
                        'messages' => [
                            Validator\Regex::NOT_MATCH => 'must include a capital letter',
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
                    'name'    => 'Identical',
                    'options' => array(
                        'token' => 'password',
                        'messages' => [
                            Validator\Identical::NOT_SAME => 'did not match.',
                        ],
                    ),
                ),
            ),
        )); // add

        //---

        $this->setInputFilter( $inputFilter );

    } // function

} // class
