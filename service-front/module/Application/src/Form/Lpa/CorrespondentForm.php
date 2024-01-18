<?php

namespace Application\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\Correspondence;
use Laminas\Form\FormInterface;

/**
 * @template T
 * @template-extends AbstractActorForm<T>
 */

class CorrespondentForm extends AbstractActorForm
{
    protected $formElements = [
        'who' => [
            'type'       => 'Hidden',
            'attributes' => [
                //  By default set the value to other for a blank form
                'value' => Correspondence::WHO_OTHER,
            ],
        ],
        'name-title' => [
            'type' => 'Text',
        ],
        'name-first' => [
            'type' => 'Text',
        ],
        'name-last' => [
            'type' => 'Text',
        ],
        'company' => [
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
        'email-address' => [
            'type' => 'Email',
        ],
        'phone-number' => [
            'type' => 'Text',
        ],
    ];

    /**
     * Flag to indicate if the data in this form can be edited - by default it is
     *
     * @var bool
     */
    private $isEditable = true;

    /**
     * Flag to indicate if a trust has been selected during the data bind
     *
     * @var bool
     */
    private $trustSelected = false;

    public function init()
    {
        $this->setName('form-correspondent');
        $this->setAttribute('data-cy', 'form-correspondent');

        //  Set the actor model so it can be used during validation
        $this->actorModel = new Correspondence();

        parent::init();
    }

    public function bind($object, $flags = FormInterface::VALUES_NORMALIZED)
    {
        //  If the data being bound represents a donor or a human attorney then the form is not editable
        //  In all other circumstances some or all of the data can be edited
        $who = (isset($object['who']) ? $object['who'] : null);
        $type = (isset($object['type']) ? $object['type'] : null);

        //  Check to see if the data should be editable
        if (
            $who == Correspondence::WHO_DONOR ||
            $who == Correspondence::WHO_CERTIFICATE_PROVIDER ||
            ($who == Correspondence::WHO_ATTORNEY && $type == 'human')
        ) {
            $this->isEditable = false;
        }

        //  Check to see if the data represents a trust and set any required data
        if ($type == 'trust') {
            //  Replace the who value for a trust attorney
            $who = $object['who'] = Correspondence::WHO_ATTORNEY;

            //  Move the name to the company field so the data binds correctly
            $object['company'] = $object['name'];
            unset($object['name']);
        }

        $this->trustSelected = ($who == Correspondence::WHO_ATTORNEY && !empty($object['company']));

        return parent::bind($object, $flags);
    }

    public function trustSelected()
    {
        return $this->trustSelected;
    }

    public function isEditable()
    {
        return $this->isEditable;
    }
}
