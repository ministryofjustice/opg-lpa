<?php

namespace Application\Form\General;

use Application\Form\AbstractCsrfForm;

/**
 * @template T
 * @template-extends AbstractCsrfForm<T>
 */

class CookieConsentForm extends AbstractCsrfForm
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
                    'yes' => [
                        'label' => 'Yes',
                        'value' => 'yes',
                    ],
                    'no' => [
                        'label' => 'No',
                        'value' => 'no',
                    ],
                ],
            ]
        ]);

        parent::init();
    }
}
