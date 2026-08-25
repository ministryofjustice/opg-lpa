<?php

declare(strict_types=1);

namespace App\Form\SharedSpace;

use App\Form\AbstractForm;
use Laminas\Filter\StringToLower;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Password;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class SharedSpaceForm extends AbstractForm
{
    public function init(): void
    {
        $this->setName('sharedSpace');

        $this->add([
            'name' => 'email',
            'type' => Email::class,
            'options' => ['label' => 'Email address'],
            'attributes' => [
                'id' => 'email',
                'class' => 'govuk-input govuk-input--width-20',
            ],
        ]);

        $this->addToInputFilter([
            'name'                   => 'email',
            'break_chain_on_failure' => true,
            'required'               => true,
            'error_message'          => 'cannot-be-empty',
            'filters'                => [
                ['name' => StringToLower::class],
            ],
        ]);

        $this->add([
            'name' => 'password',
            'type' => Password::class,
            'options' => ['label' => 'Password'],
            'attributes' => [
                'id' => 'password',
                'class' => 'govuk-input govuk-input--width-20',
            ],
        ]);

        $this->addToInputFilter([
            'name'                   => 'password',
            'break_chain_on_failure' => true,
            'required'               => true,
            'error_message'          => 'cannot-be-empty',
        ]);

        parent::init();
    }
}
