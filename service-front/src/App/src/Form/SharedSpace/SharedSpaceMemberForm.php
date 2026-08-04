<?php

declare(strict_types=1);

namespace App\Form\SharedSpace;

use App\Form\AbstractForm;
use Laminas\Form\Element\Checkbox;

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
                'checked_value'   => '1',
                'unchecked_value' => '0',
                'label'           => 'Admin',
            ],
            'attributes' => [
                'id'      => 'permissions',
                'class'   => 'govuk-checkboxes__input',
                'data-cy' => 'permissions',
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'permissions',
            'required' => false,
        ]);

        parent::init();
    }
}
