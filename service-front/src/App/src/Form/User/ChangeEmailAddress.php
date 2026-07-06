<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Authentication\AuthenticationService;
use App\Form\AbstractForm;
use App\Form\Validator\EmailAddress as EmailAddressValidator;
use Laminas\Authentication\Exception\InvalidArgumentException;
use Laminas\Validator\Callback;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class ChangeEmailAddress extends AbstractForm
{
    private ?AuthenticationService $authenticationService = null;

    public function init(): void
    {
        $this->setName('change-email-address');

        $this->add([
            'name' => 'password_current',
            'type' => 'Password',
        ]);

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
            'name'     => 'password_current',
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
                    'name'                   => 'Callback',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'callback' => [$this, 'validatePassword'],
                        'messages' => [
                            Callback::INVALID_VALUE => 'is-incorrect',
                        ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'email',
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
            ],
        ]);

        parent::init();
    }

    public function setAuthenticationService(AuthenticationService $authenticationService): void
    {
        $this->authenticationService = $authenticationService;
    }

    /**
     * Validates if a given password is correct.
     * The email address MUST already have been set on the auth service.
     */
    public function validatePassword(#[\SensitiveParameter] string $value): bool
    {
        if (!$this->authenticationService instanceof AuthenticationService) {
            throw new InvalidArgumentException('AuthenticationService not set');
        }

        $this->authenticationService->setPassword($value);

        return $this->authenticationService->verify();
    }
}
