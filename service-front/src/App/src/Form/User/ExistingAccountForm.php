<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Form\AbstractForm;
use Laminas\Form\Element\Radio;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class ExistingAccountForm extends AbstractForm
{
    public function init(): void
    {
        $this->setName('existingAccount');

        $this->add([
            'name'       => 'hasExistingAccount',
            'type'       => Radio::class,
            'required'   => true,
            'attributes' => [
                'class'          => 'govuk-radios__input',
                'div-attributes' => ['class' => 'govuk-radios__item'],
            ],
            'options'    => [
                'value_options' => [
                    'no' => [
                        'label'            => 'No, I am new to this service',
                        'value'            => 'no',
                        'label_attributes' => ['class' => 'govuk-label govuk-radios__label'],
                    ],
                    'yes' => [
                        'label'            => 'Yes, I already have an account',
                        'value'            => 'yes',
                        'label_attributes' => ['class' => 'govuk-label govuk-radios__label'],
                    ],
                ],
            ],
        ]);

        parent::init();
    }
}
