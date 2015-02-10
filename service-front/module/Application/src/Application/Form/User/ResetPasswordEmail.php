<?php
namespace Application\Form\User;

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
            'options' => [
                'label' => 'Email address',
            ],
            'name' => 'email',
            'type' => 'Email',
        ));

        $this->add(array(
            'options' => [
                'label' => 'Re-enter your email address',
            ],
            'name' => 'email_confirm',
            'type' => 'Email',
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'Submit',
        ));

        //--------------------------------

        $inputFilter = $this->getInputFilter();

        $inputFilter->add(array(
            'name'     => 'email',
            'required' => true,
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
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
                array(
                    'name'    => 'Identical',
                    'options' => array(
                        'token' => 'email',
                    ),
                ),
            ),
        ));

        //---

        $this->setInputFilter( $inputFilter );

    } // function

} // class
