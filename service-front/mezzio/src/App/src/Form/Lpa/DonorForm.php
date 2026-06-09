<?php

declare(strict_types=1);

namespace App\Form\Lpa;

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
            'type' => 'App\Form\Fieldset\Dob',
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
        $this->actorModel = new Donor();
        parent::init();
    }

    public function populateValues(iterable $data, bool $onlyBase = false): void
    {
        $data = (array) $data;

        $data['cannotSign'] = '0';
        if (array_key_exists('canSign', $data) && !$data['canSign']) {
            $data['cannotSign'] = '1';
        }

        parent::populateValues($data, $onlyBase);
    }

    protected function convertFormDataForModel($formData)
    {
        $modelData = parent::convertFormDataForModel($formData);

        if ($formData['cannotSign'] == '1') {
            $modelData['canSign'] = false;
        } elseif ($formData['cannotSign'] == '0') {
            $modelData['canSign'] = true;
        } else {
            $modelData['canSign'] = $formData['cannotSign'];
        }

        return $modelData;
    }

    protected function validateByModel()
    {
        $actorValidation = parent::validateByModel();
        $actorIsValid    = $actorValidation['isValid'];
        $actorMessages   = $actorValidation['messages'];

        $donor = new Donor($this->convertFormDataForModel($this->data));
        $donorValidation = $donor->validate();

        $donorMessages = [];
        if ($donorValidation->hasErrors()) {
            $donorMessages = $this->modelValidationMessageConverter($donorValidation, $this->data);
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
