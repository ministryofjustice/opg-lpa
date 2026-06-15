<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Form\AbstractForm;
use App\Form\Validator\Password as PasswordValidator;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

/**
 * Form to set/reset a password.
 *
 * @template T
 * @template-extends AbstractForm<T>
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
                        'min'      => 12,
                        'messages' => [
                            StringLength::TOO_SHORT => 'min-length-%min%',
                        ],
                    ],
                ],
                [
                    'name' => PasswordValidator::class,
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
        if (array_key_exists('skip_confirm_password', $this->data) && !empty($this->data['skip_confirm_password'])) {
            $this->data['password_confirm'] = $this->data['password'];

            $this->getInputFilter()
                 ->remove('password_confirm');
        }

        return parent::isValid();
    }
}
