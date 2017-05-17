<?php

namespace Application\Form\User;

use Application\Form\AbstractForm;
use Zend\Validator\Identical;
use Zend\Validator\NotEmpty;
use Zend\Validator\StringLength;

/**
 * For to request a password reset email be sent out.
 *
 * Class ResetPasswordEmail
 * @package Application\Form\User
 */
class SetPassword extends AbstractForm
{
    public function init()
    {
        $this->setName('set-password');

        $this->add([
            'name' => 'password',
            'type' => 'Password',
        ]);

        $this->add([
            'name' => 'password_confirm',
            'type' => 'Password',
        ]);

        $this->add([
            'name' => 'skip_confirm_password',
            'type' => 'Hidden',
        ]);

        //  Add data to the input filter
        $this->setUseInputFilterDefaults(false);

        $this->addToInputFilter([
            'name'     => 'password',
            'required' => true,
            'validators' => [
                [
                    'name'                   => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ],
                ],
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min'      => 8,
                        'messages' => [
                            StringLength::TOO_SHORT => 'min-length-%min%',
                        ],
                    ],
                ],
                [
                    'name' => 'Application\Form\Validator\Password',
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'password_confirm',
            'required' => true,
            'validators' => [
                [
                    'name'                   => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ],
                ],
                [
                    'name'                   => 'Identical',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'token'    => 'password',
                        'messages' => [
                            Identical::NOT_SAME => 'did-not-match',
                        ],
                    ],
                ],
            ],
        ]);

        parent::init();
    }

    public function isValid()
    {
        //  If the skip confirm password flag has been passed then remove the input filter configuration for the password confirm input
        if (array_key_exists('skip_confirm_password', $this->data) && !empty($this->data['skip_confirm_password'])) {
            $this->getInputFilter()
                 ->remove('password_confirm');
        }

        //  Continue validation
        return parent::isValid();
    }
}
