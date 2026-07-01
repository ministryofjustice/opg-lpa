<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Form\AbstractForm;
use App\Form\Validator\EmailAddress as EmailAddressValidator;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;

/**
 * Form to accept and validate email addresses.
 * Used by the password reset and resend activate token flows.
 *
 * @template T
 * @template-extends AbstractForm<T>
 */
class ConfirmEmail extends AbstractForm
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
                    'name' => EmailAddressValidator::class,
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
