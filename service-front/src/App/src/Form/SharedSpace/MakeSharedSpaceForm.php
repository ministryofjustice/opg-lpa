<?php

declare(strict_types=1);

namespace App\Form\SharedSpace;

use App\Form\AbstractForm;
use Laminas\Form\Element\Text;
use Laminas\Validator\StringLength;

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
            'attributes' => [
                'id'             => 'space-name',
                'class'          => 'govuk-input',
                'div-attributes' => ['class' => 'govuk-form-group'],
                'data-cy'        => 'space-name'
            ],
        ]);

        $this->addToInputFilter([
            'name' => 'space-name',
            'required' => true,
            'validators' => [
                [
                    'name' => StringLength::class,
                    'options' => [
                        'max' => 100,
                    ],
                ],
            ],
        ]);

        parent::init();
    }
}
