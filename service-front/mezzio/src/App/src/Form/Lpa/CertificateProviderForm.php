<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\CertificateProvider;

/**
 * @template T
 * @template-extends AbstractActorForm<T>
 */
class CertificateProviderForm extends AbstractActorForm
{
    protected $formElements = [
        'name-title' => [
            'type' => 'Text',
        ],
        'name-first' => [
            'type' => 'Text',
        ],
        'name-last' => [
            'type' => 'Text',
        ],
        'address-address1' => [
            'type' => 'Text',
        ],
        'address-address2' => [
            'type' => 'Text',
        ],
        'address-address3' => [
            'type' => 'Text',
        ],
        'address-postcode' => [
            'type' => 'Text',
        ],
        'submit' => [
            'type' => 'Submit',
        ],
    ];

    public function init()
    {
        $this->setName('form-certificate-provider');
        $this->setAttribute('data-cy', 'form-certificate-provider');
        $this->actorModel = new CertificateProvider();
        parent::init();
    }
}
