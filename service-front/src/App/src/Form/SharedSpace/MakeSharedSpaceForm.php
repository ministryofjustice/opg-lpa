<?php

declare(strict_types=1);

namespace App\Form\SharedSpace;

use App\Form\AbstractForm;
use Laminas\Form\Element\Text;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class MakeSharedSpaceForm extends AbstractForm
{
    public function init(): void
    {
        $this->setName('makeSharedSpace');

        $this->add([
            'name'       => 'space-name',
            'type'       => Text::class,
            'required'   => true,
            'attributes' => [
                'class'          => 'govuk-input',
                'div-attributes' => ['class' => 'govuk-form-group'],
                'data-cy'        => 'space-name'
            ],
        ]);

        parent::init();
    }
}
