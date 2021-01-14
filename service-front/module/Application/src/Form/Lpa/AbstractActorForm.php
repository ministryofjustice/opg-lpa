<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\AbstractData;
use Laminas\Form\FormInterface;

abstract class AbstractActorForm extends AbstractLpaForm
{

    const PREFER_NOT_TO_SAY_TITLE = 'Prefer not to say';

    /**
     * An actor model object is a Donor, Human, TrustCorporation, CertificateProvider, PeopleToNotify model object.
     *
     * @var \Opg\Lpa\DataModel\AbstractData $actor
     */
    protected $actorModel;

    public function init()
    {
        //  If the form has a title field then add the select attributes to be used in a dropdown menu
        if (isset($this->formElements['name-title'])) {
            $this->formElements['name-title']['attributes'] = [
                // added data-cy tags, but for some reason only 1 of data-cy or data-select-options actually takes. needs fixing somehow
                //  'data-cy' => 'name-title',
                  'data-select-options' => json_encode([
                    '',
                    'Mr',
                    'Mrs',
                    'Miss',
                    'Ms',
                    'Dr',
                    self::PREFER_NOT_TO_SAY_TITLE,
                    'Other',
                ]),
            ];
    }

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    protected function validateByModel()
    {
        //  Check that the actor model has been set before proceeding
        if (!$this->actorModel instanceof AbstractData) {
            throw new \RuntimeException('Actor model in the actor form must be set before the data can be validated by model');
        }

        // This merge ensured filtered data is returned without losing the metadata.
        $dataForModel = $this->convertFormDataForModel(array_merge($this->data, $this->getData()));
        $this->actorModel->populate($dataForModel);
        $validation = $this->actorModel->validate();

        $messages = [];

        //  If there are any errors then map them across if required
        if ($validation->hasErrors()) {
            // set validation message for form elements
            if ($validation->offsetExists('dob')) {
                $validation['dob-date'] = $validation['dob'];
                unset($validation['dob']);
            } elseif ($validation->offsetExists('dob.date')) {
                $validation['dob-date'] = $validation['dob.date'];
                unset($validation['dob.date']);
            }

            if (array_key_exists('phone', $dataForModel) && ($dataForModel['phone'] == null) && $validation->offsetExists('phone')) {
                $validation['phone-number'] = $validation['phone'];
                unset($validation['phone']);
            }

            if (array_key_exists('name', $dataForModel) && ($dataForModel['name'] == null) && $validation->offsetExists('name')) {
                if (array_key_exists('name-first', $this->data)) {
                    $validation['name-title'] = $validation['name'];
                    $validation['name-first'] = $validation['name'];
                    $validation['name-last'] = $validation['name'];
                    unset($validation['name']);
                }
            }

            $messages = $this->modelValidationMessageConverter($validation);
        }

        return [
            'isValid'  => !$validation->hasErrors(),
            'messages' => $messages,
        ];
    }

    /**
     * Convert form data to model-compatible input data format.
     *
     * @param array $formData. e.g. ['name-title'=>'Mr','name-first'=>'John',]
     *
     * @return array. e.g. ['name'=>['title'=>'Mr','first'=>'John',],]
     */
    protected function convertFormDataForModel($formData)
    {
        //  If it exists transfer the dob array into a string
        if (array_key_exists('dob-date', $formData) && is_array($formData['dob-date'])) {
            $dobDateArr = $formData['dob-date'];
            $dobDateStr = null;

            if (!empty($dobDateArr['year']) && !empty($dobDateArr['month']) && !empty($dobDateArr['day'])) {
                $dobDateStr = $dobDateArr['year'] . '-' . $dobDateArr['month'] . '-' . $dobDateArr['day'];
            }

            $formData['dob-date'] = $dobDateStr;
        }

        $dataForModel = parent::convertFormDataForModel($formData);

        if (isset($dataForModel['email']) && ($dataForModel['email']['address'] == "")) {
            $dataForModel['email'] = null;
        }

        if (isset($dataForModel['phone']) && ($dataForModel['phone']['number'] == "")) {
            $dataForModel['phone'] = null;
        }

        if (isset($dataForModel['name']) && is_array($dataForModel['name']) && ($dataForModel['name']['title'] == "") && ($dataForModel['name']['first'] == "") && ($dataForModel['name']['last'] == "")) {
            $dataForModel['name'] = null;
        }

        // If they have opted not to enter a title, set it to null
        if (isset($dataForModel['name']['title']) && ($dataForModel['name']['title'] == self::PREFER_NOT_TO_SAY_TITLE)) {
            $dataForModel['name']['title'] = null;
        }

        return $dataForModel;
    }


    public function bind($modelizedDataArray, $flags = FormInterface::VALUES_NORMALIZED)
    {
        if (array_key_exists('name-title', $modelizedDataArray) && is_null($modelizedDataArray['name-title'])) {
            $modelizedDataArray['name-title'] = self::PREFER_NOT_TO_SAY_TITLE;
        }

        return parent::bind($modelizedDataArray);
    }

    /**
     * Function to set the actor data (existing type and names for duplicate comparisons) for all actors associated with the current LPA as a data attribute
     *
     * @param $actorType
     * @param array $actorNames
     */
    public function setActorData($actorType, array $actorNames)
    {
        $this->setAttribute('data-actor-type', $actorType);
        $this->setAttribute('data-actor-names', json_encode($actorNames));
    }
}
