<?php

namespace Application\Form\User;

use Application\Form\AbstractCsrfForm;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

/**
 * For to request a password reset email be sent out.
 *
 * @template T
 * @template-extends AbstractCsrfForm<T>
 */

class SetPassword extends AbstractCsrfForm
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
                [
                    'name'    => 'Identical',
                    'options' => [
                        'token'    => 'password_confirm',
                        'messages' => [
                            Identical::NOT_SAME => 'did-not-match',
                        ],
                    ],
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
            ],
        ]);

        parent::init();
    }

    public function isValid(): bool
    {
        //  If the skip confirm password flag has been passed then set the password value as the password confirm value to pass validation
        if (array_key_exists('skip_confirm_password', $this->data) && !empty($this->data['skip_confirm_password'])) {
            $this->data['password_confirm'] = $this->data['password'];

            //  Remove confirm password input filter to stop validation error for hidden field
            $this->getInputFilter()
                 ->remove('password_confirm');
        }

        //  Continue validation
        return parent::isValid();
    }
}
