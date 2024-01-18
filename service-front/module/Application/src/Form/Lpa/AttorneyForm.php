<?php

namespace Application\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\Attorneys\Human;

/**
 * @template T
 * @template-extends AbstractActorForm<T>
 */

class AttorneyForm extends AbstractActorForm
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
        'dob-date' => [
            'type' => 'Application\Form\Fieldset\Dob',
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
        $this->setName('form-attorney');
        $this->setAttribute('data-cy', 'form-attorney');

        //  Set the actor model so it can be used during validation
        $this->actorModel = new Human();

        parent::init();
    }
}
