<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Form\Validator\EmailAddress as EmailAddressValidator;
use Laminas\Validator\NotEmpty;

/**
 * Signing in to an existing Make account to link it to GOV.UK One Login. The same as
 * signing in, but the email address must also be in a valid format.
 *
 * @template T
 * @template-extends Login<T>
 */
class LinkAccountForm extends Login
{
    public function init()
    {
        parent::init();

        $this->setName('linkAccount');

        // Replace, rather than merge with, the sign-in email input, whose single 'cannot-be-empty'
        // message hides the Email element's own pattern validator.
        $this->getInputFilter()->remove('email');

        $this->addToInputFilter([
            'name'       => 'email',
            'required'   => true,
            'filters'    => [
                ['name' => 'StringToLower'],
            ],
            'validators' => [
                [
                    'name'                   => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [NotEmpty::IS_EMPTY => 'cannot-be-empty'],
                    ],
                ],
                ['name' => EmailAddressValidator::class],
            ],
        ]);
    }
}
