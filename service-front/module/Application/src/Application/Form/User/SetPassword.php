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
                        'messageTemplates' => [
                            Validator\StringLength::TOO_SHORT => 'Your password has to be at least %min% characters long.',
                        ],
                    ),
                ),
                array(
                    'name'    => 'Regex',
                    'options' => array(
                        'pattern' => '/.*[0-9].*/',
                        'messageTemplates' => [
                            Validator\Regex::NOT_MATCH => 'Your password must include a number.',
                        ],
                    ),
                ),
                array(
                    'name'    => 'Regex',
                    'options' => array(
                        'pattern' => '/.*[a-z].*/',
                        'messageTemplates' => [
                            Validator\Regex::NOT_MATCH => 'Your password must include a lower-case letter.',
                        ],
                    ),
                ),
                array(
                    'name'    => 'Regex',
                    'options' => array(
                        'pattern' => '/.*[A-Z].*/',
                        'messageTemplates' => [
                            Validator\Regex::NOT_MATCH => 'Your password must include a capital letter.',
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
                        'messageTemplates' => [
                            Validator\Identical::NOT_SAME => 'Your passwords did not match.',
                        ],
                    ),
                ),
            ),
        )); // add

        //---

        $this->setInputFilter( $inputFilter );

    } // function

} // class
