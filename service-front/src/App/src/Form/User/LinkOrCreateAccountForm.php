<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Form\AbstractForm;
use Laminas\Form\Element\Radio;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class LinkOrCreateAccountForm extends AbstractForm
{
    public function init(): void
    {
        $this->setName('linkOrCreateAccount');

        $this->add([
            'name'       => 'choice',
            'type'       => Radio::class,
            'required'   => true,
            'attributes' => [
                'class'          => 'govuk-radios__input',
                'div-attributes' => ['class' => 'govuk-radios__item'],
            ],
            'options'    => [
                'value_options' => [
                    'link' => [
                        'label' => 'Yes',
                        'value' => 'link',
                        'label_attributes' => ['class' => 'govuk-label govuk-radios__label'],
                    ],
                    'create'  => [
                        'label' => 'No, create a Make account',
                        'value' => 'create',
                        'label_attributes' => ['class' => 'govuk-label govuk-radios__label'],
                    ],
                ],
            ],
        ]);

        parent::init();
    }
}
