<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use MakeShared\DataModel\AbstractData;
use Laminas\Form\FormInterface;

/**
 * @template T
 * @template-extends AbstractLpaForm<T>
 */
abstract class AbstractActorForm extends AbstractLpaForm
{
    public const PREFER_NOT_TO_SAY_TITLE = 'Prefer not to say';

    /** @var \MakeShared\DataModel\AbstractData $actorModel */
    protected $actorModel;

    public function init()
    {
        if (isset($this->formElements['email-address'])) {
            $this->formElements['email-address']['filters'][] = [
                'name' => 'StringToLower',
            ];
        }

        if (isset($this->formElements['name-title'])) {
            $this->formElements['name-title']['attributes'] = [
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

    protected function validateByModel()
    {
        if (!$this->actorModel instanceof AbstractData) {
            throw new \RuntimeException(
                'Actor model in the actor form must be set before the data can be validated by model'
            );
        }

        $dataForModel = $this->convertFormDataForModel(array_merge($this->data, $this->getData()));
        $this->actorModel->populate($dataForModel);
        $validation = $this->actorModel->validate();

        $messages = [];

        if ($validation->hasErrors()) {
            if ($validation->offsetExists('dob')) {
                $validation['dob-date'] = $validation['dob'];
                unset($validation['dob']);
            } elseif ($validation->offsetExists('dob.date')) {
                $validation['dob-date'] = $validation['dob.date'];
                unset($validation['dob.date']);
            }

            if (
                array_key_exists('phone', $dataForModel) &&
                ($dataForModel['phone'] == null) && $validation->offsetExists('phone')
            ) {
                $validation['phone-number'] = $validation['phone'];
                unset($validation['phone']);
            }

            if (
                array_key_exists('name', $dataForModel) &&
                $dataForModel['name'] == null &&
                $validation->offsetExists('name')
            ) {
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

    protected function convertFormDataForModel($formData)
    {
        $formDataAsArray = (array) $formData;
        if (array_key_exists('dob-date', $formDataAsArray) && is_array($formDataAsArray['dob-date'])) {
            $dobDateArr = $formDataAsArray['dob-date'];
            $dobDateStr = null;

            if (!empty($dobDateArr['year']) && !empty($dobDateArr['month']) && !empty($dobDateArr['day'])) {
                $dobDateStr = $dobDateArr['year'] . '-' . $dobDateArr['month'] . '-' . $dobDateArr['day'];
            }

            $formDataAsArray['dob-date'] = $dobDateStr;
        }

        $dataForModel = parent::convertFormDataForModel($formDataAsArray);

        if (isset($dataForModel['email']) && ($dataForModel['email']['address'] == '')) {
            $dataForModel['email'] = null;
        }

        if (isset($dataForModel['phone']) && ($dataForModel['phone']['number'] == '')) {
            $dataForModel['phone'] = null;
        }

        if (
            isset($dataForModel['name']) &&
            is_array($dataForModel['name']) &&
            $dataForModel['name']['title'] == '' &&
            $dataForModel['name']['first'] == '' &&
            $dataForModel['name']['last'] == ''
        ) {
            $dataForModel['name'] = null;
        }

        if (
            isset($dataForModel['name']['title']) &&
            $dataForModel['name']['title'] == self::PREFER_NOT_TO_SAY_TITLE
        ) {
            $dataForModel['name']['title'] = null;
        }

        return $dataForModel;
    }

    public function bind($object, $flags = FormInterface::VALUES_NORMALIZED)
    {
        if (array_key_exists('name-title', $object) && is_null($object['name-title'])) {
            $object['name-title'] = self::PREFER_NOT_TO_SAY_TITLE;
        }

        return parent::bind($object, $flags);
    }

    public function setActorData($actorType, array $actorNames)
    {
        $this->setAttribute('data-actor-type', $actorType);
        $this->setAttribute('data-actor-names', json_encode($actorNames));
    }
}
