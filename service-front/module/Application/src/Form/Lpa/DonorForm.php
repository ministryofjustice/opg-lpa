<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Donor;

class DonorForm extends AbstractActorForm
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
        'otherNames' => [
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
        'canSign' => [
            'type' => 'Checkbox',
            'options' => [
                'checked_value'   => '0',
                'unchecked_value' => '1',
            ],
        ],
        'submit' => [
            'type' => 'Submit',
        ],
    ];

    public function init()
    {
        $this->setName('form-donor');
        $this->setAttribute('data-cy', 'form-donor');

        //  Set the actor model so it can be used during validation
        $this->actorModel = new Donor();

        parent::init();
    }
}
