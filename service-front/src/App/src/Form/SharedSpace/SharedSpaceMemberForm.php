<?php

declare(strict_types=1);

namespace App\Form\SharedSpace;

use App\Form\AbstractForm;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Radio;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class SharedSpaceMemberForm extends AbstractForm
{
    public function init(): void
    {
        $this->setName('sharedSpaceMember');

        $this->add([
            'name'       => 'permissions',
            'type'       => Checkbox::class,
            'options'    => [
                'label'           => 'Make this member an admin',
            ],
            'attributes' => [
                'id'      => 'permissions',
                'class'   => 'govuk-checkboxes__input',
                'data-cy' => 'permissions',
            ],
        ]);

        $this->add([
            'name'       => 'status',
            'type'       => Radio::class,
            'attributes' => [
                'id'             => 'status',
                'class'          => 'govuk-radios__input',
                'div-attributes' => ['class' => 'govuk-radios__item'],
            ],
            'options' => [
                'value_options' => [
                    'allow' => [
                        'label'      => 'Active - member can access this shared space',
                        'value'      => 'active',
                        'attributes' => ['data-cy' => 'active'],
                    ],
                    'suspend' => [
                        'label'      => 'Suspended - member cannot access this shared space',
                        'value'      => 'inactive',
                        'attributes' => ['data-cy' => 'inactive', 'data-aria-controls' => 'conditional-suspended'],
                        'hint'       => 'For example, for extended leave',
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'permissions',
            'required' => false,
        ]);

        parent::init();
    }
}
