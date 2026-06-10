<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Authentication\AuthenticationService;
use Laminas\Authentication\Exception\InvalidArgumentException;
use Laminas\Validator\Callback;
use Laminas\Validator\NotEmpty;

/**
 * @template T
 * @template-extends SetPassword<T>
 */
class ChangePassword extends SetPassword
{
    private ?AuthenticationService $authenticationService = null;

    public function init(): void
    {
        parent::init();

        $this->setName('change-password');

        $this->add([
            'name' => 'password_current',
            'type' => 'Password',
        ]);

        $this->setUseInputFilterDefaults(false);

        $this->addToInputFilter([
            'name'     => 'password_current',
            'required' => true,
            'validators' => [
                [
                    'name'    => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ],
                ],
                [
                    'name'    => 'Callback',
                    'break_chain_on_failure' => true,
                    'options' => [
                        'callback' => [$this, 'validatePassword'],
                        'messages' => [
                            Callback::INVALID_VALUE => 'is-incorrect',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function setAuthenticationService(AuthenticationService $authenticationService): void
    {
        $this->authenticationService = $authenticationService;
    }

    /**
     * Validates if a given password is correct.
     * The email address MUST already have been set on the auth service.
     */
    public function validatePassword(string $value): bool
    {
        if (!$this->authenticationService instanceof AuthenticationService) {
            throw new InvalidArgumentException('AuthenticationService not set');
        }

        $this->authenticationService->setPassword($value);

        return $this->authenticationService->verify();
    }
}
