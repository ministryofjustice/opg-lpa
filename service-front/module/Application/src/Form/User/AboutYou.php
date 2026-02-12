<?php

namespace Application\Form\User;

use Application\Form\Lpa\AbstractActorForm;
use MakeShared\DataModel\User\User;
use Laminas\Form\Form;
use Laminas\Form\FormInterface;

/**
 * @template T
 * @template-extends AbstractActorForm<T>
 */

class AboutYou extends AbstractActorForm
{
    /**
     * @var array
     */
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

    /**
     * init
     */
    public function init()
    {
        $this->setName('about-you');

        $this->actorModel = new User();

        parent::init();
    }

    /**
     * Set data in the form - remove the the date of birth and address data
     * if it has been totally omitted
     *
     * @param iterable $data
     * @return Form&static
     */
    public function setData($data)
    {
        $dataArray = (array)$data;
        $this->filterData($dataArray);
        return parent::setData($data);
    }

    /**
     * Retrieve the validated data - used AFTER form validation to
     * retrieve an array of data before sending it to the API
     *
     * @param  int $flag
     * @return T|object|array<string, mixed>
     */
    public function getData($flag = FormInterface::VALUES_NORMALIZED)
    {
        $data = parent::getData($flag);

        $this->filterData($data);

        //  Filter the date of birth here into a single value or remove if fully missing
        //  This can not be done in the filter function below because it will replace the array value that is necessary
        //  to keep the inputs populated if we are returning to the input screen with errors
        if (array_key_exists('dob-date', $data) && is_array($data['dob-date'])) {
            $dobDateArr = $data['dob-date'];

            if (!empty($dobDateArr['year']) && !empty($dobDateArr['month']) && !empty($dobDateArr['day'])) {
                $data['dob-date'] = $dobDateArr['year'] . '-' . $dobDateArr['month'] . '-' . $dobDateArr['day'];
            }
        }

        return $data;
    }

    /**
     * Filter an array to remove any parts of the data that are fully missing
     *
     * @param array $data
     */
    private function filterData(array &$data)
    {
        //  If the date of birth is empty then remove it here completely
        if (
            array_key_exists('dob-date', $data)
            && is_array($data['dob-date'])
            && empty($data['dob-date']['year'])
            && empty($data['dob-date']['month'])
            && empty($data['dob-date']['day'])
        ) {
            unset($data['dob-date']);
        }

        //  If the address is empty then remove it - it is optional
        if (
            empty($data['address-address1']) &&
            empty($data['address-address2']) &&
            empty($data['address-address3']) &&
            empty($data['address-postcode'])
        ) {
            $data['address'] = null;
        }

        // If the user selected 'Prefer not to say' as their title, then save their title value as null
        if (array_key_exists('name-title', $data) && $data['name-title'] == self::PREFER_NOT_TO_SAY_TITLE) {
            $data['name-title'] = null;
        }
    }
}
