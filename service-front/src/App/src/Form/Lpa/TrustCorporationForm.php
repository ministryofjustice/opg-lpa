<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;

/**
 * @template T
 * @template-extends AbstractActorForm<T>
 */
class TrustCorporationForm extends AbstractActorForm
{
    protected $formElements = [
        'name' => [
            'type' => 'Text',
        ],
        'number' => [
            'type' => 'Text',
        ],
        'email-address' => [
            'type' => 'Email',
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
        $this->setName('form-trust-corporation');
        $this->actorModel = new TrustCorporation();
        parent::init();
    }
}
