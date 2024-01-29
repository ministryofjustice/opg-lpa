<?php

namespace Application\Form\User;

use Application\Form\AbstractCsrfForm;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;

/**
 * Form to accept and validate email addresses
 * Used by the password reset and resend activate token flows
 *
 * @template T
 * @template-extends AbstractCsrfForm<T>
 */
class ConfirmEmail extends AbstractCsrfForm
{
    public function init()
    {
        $this->setName('confirm-email');

        $this->add([
            'name' => 'email',
            'type' => 'Email',
        ]);

        $this->add([
            'name' => 'email_confirm',
            'type' => 'Email',
        ]);

        //  Add data to the input filter
        $this->setUseInputFilterDefaults(false);

        $this->addToInputFilter([
            'name'     => 'email',
            'required' => true,
            'validators' => [
                [
                    'name'                   => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages'           => [
                            NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ],
                ],
                [
                    'name' => 'Application\Form\Validator\EmailAddress',
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'email_confirm',
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
                    'name'    => 'Identical',
                    'options' => [
                        'token'    => 'email',
                        'messages' => [
                            Identical::NOT_SAME => 'did-not-match',
                        ],
                    ],
                ],
            ]
        ]);

        parent::init();
    }
}
