<?php

declare(strict_types=1);

namespace App\Form\General;

use App\Form\AbstractForm;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class CookieConsentForm extends AbstractForm
{
    public function init()
    {
        $this->setName('cookieConsent');

        $this->add([
            'name'       => 'usageCookies',
            'type'       => 'Radio',
            'attributes' => [
                'class'          => 'govuk-radios__input',
                'div-attributes' => ['class' => 'govuk-radios__item'],
            ],
            'required'   => true,
            'options'    => [
                'value_options' => [
                    'yes' => ['label' => 'Yes', 'value' => 'yes', 'label_attributes' => ['class' => 'govuk-label govuk-radios__label']],
                    'no'  => ['label' => 'No', 'value' => 'no', 'label_attributes' => ['class' => 'govuk-label govuk-radios__label']],
                ],
            ],
        ]);

        parent::init();
    }
}
