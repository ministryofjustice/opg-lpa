<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;

class PeopleToNotifyForm extends AbstractActorForm
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
        $this->setName('form-people-to-notify');
        $this->setAttribute('data-cy', 'form-people-to-notify');

        //  Set the actor model so it can be used during validation
        $this->actorModel = new NotifiedPerson();

        parent::init();
    }
}
