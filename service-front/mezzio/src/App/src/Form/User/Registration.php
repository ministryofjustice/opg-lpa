<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Form\Validator\EmailAddress as EmailAddressValidator;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;

/**
 * @template T
 * @template-extends SetPassword<T>
 */
class Registration extends SetPassword
{
    public function init()
    {
        $this->add([
            'name' => 'email',
            'type' => 'Email',
        ]);

        $this->add([
            'name' => 'email_confirm',
            'type' => 'Email',
        ]);

        parent::init();
        $this->setName('registration');

        $this->add([
            'name'    => 'terms',
            'type'    => 'Checkbox',
            'options' => [
                'use_hidden_element' => false,
            ]
        ]);

        $this->setUseInputFilterDefaults(false);

        $this->addToInputFilter([
            'name'     => 'email',
            'required' => true,
            'filters'  => [
                [
                    'name' => 'StringToLower'
                ],
            ],
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
                ]
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
                    'name'                   => 'Identical',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'token'    => 'email',
                        'messages' => [
                            Identical::NOT_SAME => 'did-not-match',
                        ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'          => 'terms',
            'required'      => true,
            'error_message' => 'must-be-checked',
            'validators'    => [
                [
                    'name'    => 'Identical',
                    'break_chain_on_failure' => true,
                    'options' => [
                        'token' => '1',
                        'literal' => true,
                        'messages' => [
                            Identical::NOT_SAME => 'must-be-checked',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
