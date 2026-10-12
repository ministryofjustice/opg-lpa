<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Form\AbstractForm;
use Laminas\Form\Element\Radio;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class KeepYourLpasForm extends AbstractForm
{
    public function init(): void
    {
        $this->setName('keepYourLpas');

        $this->add([
            'name'       => 'keepExistingAccount',
            'type'       => Radio::class,
            'required'   => true,
            'attributes' => [
                'class'          => 'govuk-radios__input',
                'div-attributes' => ['class' => 'govuk-radios__item'],
            ],
            'options'    => [
                'value_options' => [
                    'yes' => [
                        'label'            => 'Yes',
                        'value'            => 'yes',
                        'label_attributes' => ['class' => 'govuk-label govuk-radios__label'],
                    ],
                    'no' => [
                        'label'            => 'No',
                        'value'            => 'no',
                        'label_attributes' => ['class' => 'govuk-label govuk-radios__label'],
                    ],
                ],
            ],
        ]);

        parent::init();
    }
}
