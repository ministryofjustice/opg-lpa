<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Form\AbstractForm;
use Laminas\Form\Element\Radio;
use MakeShared\OneLogin\UserType;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class WhichBestDescribesYouForm extends AbstractForm
{
    public function init(): void
    {
        $this->setName('whichBestDescribesYou');

        $this->add([
            'name'       => 'userType',
            'type'       => Radio::class,
            'required'   => true,
            'attributes' => [
                'class'          => 'govuk-radios__input',
                'div-attributes' => ['class' => 'govuk-radios__item'],
            ],
            'options'    => [
                'value_options' => [
                    UserType::Lay->value => [
                        'label'            => 'I will be making LPAs for myself, family or friends',
                        'value'            => UserType::Lay->value,
                        'label_attributes' => ['class' => 'govuk-label govuk-radios__label'],
                    ],
                    UserType::Professional->value => [
                        'label'            => 'I will be making LPAs for other people as part of my work (voluntary or paid)',
                        'value'            => UserType::Professional->value,
                        'label_attributes' => ['class' => 'govuk-label govuk-radios__label'],
                    ],
                ],
            ],
        ]);

        parent::init();
    }
}
