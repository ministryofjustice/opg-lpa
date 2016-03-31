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
        $this->setUseInputFilterDefaults(false);
        
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
                    'name'    => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options' => array(
                        'messages' => [
                            Validator\NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ),
                ),
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
                    'options' => array(
                        'token' => 'email',
                        'messages' => [
                            Validator\Identical::NOT_SAME => 'did-not-match',
                        ],
                    ),
                ),
            )
        ));

        //---

        $this->setInputFilter( $inputFilter );

    } // function

} // class
