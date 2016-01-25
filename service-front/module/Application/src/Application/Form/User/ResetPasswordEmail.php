<?php
namespace Application\Form\User;

use Zend\Validator;

/**
 * For to request a password reset email be sent out.
 *
 * Class ResetPasswordEmail
 * @package Application\Form\User
 */
class ResetPasswordEmail extends AbstractForm {

    public function __construct(){

        parent::__construct('reset-password-email');

        //---

        $this->add(array(
            'name' => 'email',
            'type' => 'Email',
        ));

        $this->add(array(
            'name' => 'email_confirm',
            'type' => 'Email',
        ));

        //--------------------------------

        $inputFilter = $this->getInputFilter();

        $inputFilter->add(array(
            'name'     => 'email',
            'required' => true,
            'error_message' => 'You need to enter your email address',
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
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
            'error_message' => 'You need to re-enter your email address',
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
                            Validator\Identical::NOT_SAME => 'did not match',
                        ],
                    ),
                ),
            ),
        ));

        //---

        $this->setInputFilter( $inputFilter );

    } // function

} // class
