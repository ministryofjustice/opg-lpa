<?php

declare(strict_types=1);

namespace App\Form\SharedSpace;

use App\Form\AbstractForm;
use App\Form\Validator\EmailAddress;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Checkbox;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class InviteMemberForm extends AbstractForm
{
    public function init(): void
    {
        $this->setName('inviteMember');

        $this->add([
            'name'       => 'firstNames',
            'type'       => Text::class,
            'required'   => true,
            'options' => [
                'label' => 'First names',
                'hint' => 'Include any middle names'
            ],
            'attributes' => [
                'id' => 'firstNames',
                'class' => 'govuk-input govuk-input--width-20',
            ],
        ]);

        $this->addToInputFilter([
            'name' => 'firstNames',
            'required' => true,
            'validators' => [
                [
                    'name'                   => NotEmpty::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'Enter the shared space members first name',
                        ],
                    ],
                ],
                [
                    'name'    => StringLength::class,
                    'options' => [
                        'max'      => 50,
                        'messages' => [StringLength::TOO_LONG => 'The shared space members first name must be 50 characters or less'],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'lastName',
            'type'       => Text::class,
            'required'   => true,
            'options' => [
                'label' => 'Last name',
            ],
            'attributes' => [
                'id' => 'lastName',
                'class' => 'govuk-input govuk-input--width-20',
            ],
        ]);

        $this->addToInputFilter([
            'name' => 'lastName',
            'required' => true,
            'validators' => [
                [
                    'name'                   => NotEmpty::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'Enter the shared space members last name',
                        ],
                    ],
                ],
                [
                    'name'    => StringLength::class,
                    'options' => [
                        'max'      => 50,
                        'messages' => [StringLength::TOO_LONG => 'The shared space members last name must be 50 characters or less'],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'email',
            'type'       => Email::class,
            'required'   => true,
            'options' => [
                'label' => 'Email address',
            ],
            'attributes' => [
                'id' => 'email',
                'class' => 'govuk-input',
            ],
        ]);

        $this->addToInputFilter([
            'name' => 'email',
            'required' => true,
            'validators' => [
                [
                    'name'                   => NotEmpty::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [NotEmpty::IS_EMPTY => 'Enter the shared space members email address'],
                    ],
                ],
                [
                    'name'    => StringLength::class,
                    'break_chain_on_failure' => true,
                    'options' => [
                        'max'      => 254,
                        'messages' => [StringLength::TOO_LONG => 'The shared space members email must be 254 characters or less'],
                    ],
                ],
                [
                    'name' => EmailAddress::class,
                    'options' => [
                        'messages' => [
                            EmailAddress::INVALID_EMAIL => 'Enter an email address in the correct format, like name@example.com'
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name' => 'permissions',
            'type' => Checkbox::class,
            'required' => false,
            'options' => [
                'label' => 'Make this person an admin',
            ],
            'attributes' => [
                'id' => 'permissions',
                'class' => 'govuk-checkboxes__input',
            ],
            'validators' => [],
        ]);

        parent::init();
    }
}
