<?php

namespace Application\Form\User;

use Application\Model\Service\Authentication\AuthenticationService;
use Laminas\Authentication\Exception\InvalidArgumentException;
use Laminas\Validator\Callback;
use Laminas\Validator\NotEmpty;

/**
 * @template T
 * @template-extends SetPassword<T>
 */

class ChangePassword extends SetPassword
{
    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    public function init()
    {
        parent::init();

        $this->setName('change-password');

        $this->add([
            'name' => 'password_current',
            'type' => 'Password',
        ]);

        //  Add data to the input filter
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
                        'callback' => [ $this, 'validatePassword' ],
                        'messages' => [
                            Callback::INVALID_VALUE => 'is-incorrect',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Set the Authentication Service used to validate the user's password.
     *
     * @param AuthenticationService $authenticationService
     */
    public function setAuthenticationService(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    /**
     * Validates if a given password is correct.
     *
     * The email address MUST already have been set.
     *
     * @param $value string The value from the password text field.
     * @return bool
     */
    public function validatePassword($value)
    {
        if (!$this->authenticationService instanceof AuthenticationService) {
            throw new InvalidArgumentException('AuthenticationService not set');
        }

        // Set the password in the adapter
        $this->authenticationService->setPassword($value);

        return $this->authenticationService->verify();
    }
}
