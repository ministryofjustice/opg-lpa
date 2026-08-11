<?php

declare(strict_types=1);

namespace App\Form\SharedSpace;

use App\Form\AbstractForm;
use App\Form\Validator\EmailAddress;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Checkbox;

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
            ],
            'attributes' => [
                'id' => 'firstNames',
                'class' => 'govuk-input govuk-input--width-20',
            ],
        ]);

        $this->addToInputFilter([
            'name' => 'firstNames',
            'required' => true,
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
        ]);

        $this->add([
            'name'       => 'email',
            'type'       => Email::class,
            'required'   => true,
            'options' => [
                'label' => 'Email',
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
                ['name' => EmailAddress::class],
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
