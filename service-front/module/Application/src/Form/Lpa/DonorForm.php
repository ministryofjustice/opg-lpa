<?php

namespace Application\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\Donor;

/**
 * @template T
 * @template-extends AbstractActorForm<T>
 */

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
        'cannotSign' => [
            'type' => 'Checkbox',
            'options' => [
                'checked_value'   => '1',
                'unchecked_value' => '0',
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

    public function populateValues(iterable $data, bool $onlyBase = false): void
    {
        $data = (array) $data;

        // canSign is stored in the db, but our cannotSign checkbox is ticked if the
        // donor *cannot* sign; so this is where we do the inversion from
        // the canSign property (in the model/db) to cannotSign checkbox (in the UI)
        $data['cannotSign'] = '0';
        if (array_key_exists('canSign', $data) && !$data['canSign']) {
            $data['cannotSign'] = '1';
        }

        parent::populateValues($data, $onlyBase);
    }

    /**
     * Convert form data to model-compatible input data format.
     * This is where we map the cannotSign checkbox to the canSign property
     * on the donor: if cannotSign is true, canSign is false (and vice versa).
     *
     * @param array $formData
     * @return array
     */
    protected function convertFormDataForModel($formData)
    {
        $modelData = parent::convertFormDataForModel($formData);

        // Set canSign (on the model) as the inverse of cannotSign (from the UI checkbox)
        if ($formData['cannotSign'] == '1') {
            $modelData['canSign'] = false;
        } elseif ($formData['cannotSign'] == '0') {
            $modelData['canSign'] = true;
        } else {
            $modelData['canSign'] = $formData['cannotSign'];
        }

        return $modelData;
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    protected function validateByModel()
    {
        // Validate the actor
        $actorValidation = parent::validateByModel();
        $actorIsValid = $actorValidation['isValid'];
        $actorMessages = $actorValidation['messages'];

        // Validate the donor
        $donor = new Donor($this->convertFormDataForModel($this->data));
        $donorValidation = $donor->validate();

        $donorMessages = [];
        if ($donorValidation->hasErrors()) {
            $donorMessages = $this->modelValidationMessageConverter($donorValidation, $this->data);

            // We only want the validation message for the fields not already validated
            // by the actor validation; see AbstractActorForm->validateByModel()
            // i.e. not dob, dob.date, phone or name
            unset($donorMessages['dob']);
            unset($donorMessages['dob.date']);
            unset($donorMessages['phone']);
            unset($donorMessages['name']);
        }

        return [
            'isValid'  => (!$donorValidation->hasErrors()) && $actorIsValid,
            'messages' => array_merge($actorMessages, $donorMessages),
        ];
    }
}
