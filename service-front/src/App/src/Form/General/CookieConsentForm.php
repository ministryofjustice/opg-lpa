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
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'required'   => true,
            'options'    => [
                'value_options' => [
                    'yes' => ['label' => 'Yes', 'value' => 'yes'],
                    'no'  => ['label' => 'No', 'value' => 'no'],
                ],
            ],
        ]);

        parent::init();
    }
}
