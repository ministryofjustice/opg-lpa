<?php

namespace Application\Form\User;

use Zend\Validator;

/**
 * For to request a password reset email be sent out.
 *
 * Class ResetPasswordEmail
 * @package Application\Form\User
 */
class SetPassword extends AbstractForm
{

    public function __construct($formName = 'set-password')
    {
        parent::__construct($formName);

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
                    'name' => 'Application\Form\Validator\Password',
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

        $this->setInputFilter($inputFilter);
    } // function
} // class
