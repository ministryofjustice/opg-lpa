<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Form\Lpa\AbstractActorForm;
use MakeShared\DataModel\User\User;
use Laminas\Form\FormInterface;

/**
 * @template T
 * @template-extends AbstractActorForm<T>
 */
class AboutYou extends AbstractActorForm
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
            'type' => 'App\Form\Fieldset\Dob',
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
    ];

    public function init()
    {
        $this->setName('about-you');
        $this->actorModel = new User();
        parent::init();
    }

    public function setData($data)
    {
        $dataArray = (array) $data;
        $this->filterData($dataArray);
        return parent::setData($data);
    }

    public function getData($flag = FormInterface::VALUES_NORMALIZED)
    {
        $data = parent::getData($flag);
        $this->filterData($data);

        if (array_key_exists('dob-date', $data) && is_array($data['dob-date'])) {
            $dobDateArr = $data['dob-date'];
            if (!empty($dobDateArr['year']) && !empty($dobDateArr['month']) && !empty($dobDateArr['day'])) {
                $data['dob-date'] = $dobDateArr['year'] . '-' . $dobDateArr['month'] . '-' . $dobDateArr['day'];
            }
        }

        return $data;
    }

    private function filterData(array &$data): void
    {
        if (
            array_key_exists('dob-date', $data)
            && is_array($data['dob-date'])
            && empty($data['dob-date']['year'])
            && empty($data['dob-date']['month'])
            && empty($data['dob-date']['day'])
        ) {
            unset($data['dob-date']);
        }

        if (
            empty($data['address-address1']) &&
            empty($data['address-address2']) &&
            empty($data['address-address3']) &&
            empty($data['address-postcode'])
        ) {
            $data['address'] = null;
        }

        if (array_key_exists('name-title', $data) && $data['name-title'] == self::PREFER_NOT_TO_SAY_TITLE) {
            $data['name-title'] = null;
        }
    }
}
