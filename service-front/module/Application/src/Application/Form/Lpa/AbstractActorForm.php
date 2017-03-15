<?php
namespace Application\Form\Lpa;

abstract class AbstractActorForm extends AbstractForm
{
    /**
     * An actor model object is a Donor, Human, TrustCorporation, CertificateProvider, PeopleToNotify model object.
     *
     * @var \Opg\Lpa\DataModel\AbstractData $actor
     */
    protected $actorModel;

    /**
     * Validate form input data through model validators.
     *
     * @return [isValid => bool, messages => [<formElementName> => string, ..]]
     */
    public function validateByModel()
    {
        $dataForModel = $this->convertFormDataForModel($this->data);

        $this->actorModel->populate($dataForModel);
        $validation = $this->actorModel->validate();

        // set validation message for form elements
        if ($validation->offsetExists('dob')) {
            $validation['dob-date'] = $validation['dob'];
            unset($validation['dob']);
        } elseif ($validation->offsetExists('dob.date')) {
            $validation['dob-date'] = $validation['dob.date'];
            unset($validation['dob.date']);
        }

        if (array_key_exists('email', $dataForModel) && ($dataForModel['email'] == null) && $validation->offsetExists('email')) {
            $validation['email-address'] = $validation['email'];
            unset($validation['email']);
        }

        if (array_key_exists('phone', $dataForModel) && ($dataForModel['phone'] == null) && $validation->offsetExists('phone')) {
            $validation['phone-number'] = $validation['phone'];
            unset($validation['phone']);
        }

        if (array_key_exists('name', $dataForModel) && ($dataForModel['name'] == null) && $validation->offsetExists('name')) {
            if (array_key_exists('name-first', $this->data)) {
                $validation['name-first'] = $validation['name'];
                $validation['name-last']  = $validation['name'];
                unset($validation['name']);
            }
        }

        if (empty($message) && (count($validation) == 0)) {
            return ['isValid'=>true, 'messages' => []];
        } else {
            return [
                'isValid'=>false,
                'messages' => $this->modelValidationMessageConverter($validation),
            ];
        }
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
        if (array_key_exists('dob-date', $formData)) {
            if (($formData['dob-date']['year']>0) && ($formData['dob-date']['month']>0) && ($formData['dob-date']['day']>0)) {
                $formData['dob-date'] = $formData['dob-date']['year'] . '-' . $formData['dob-date']['month'] . '-' . $formData['dob-date']['day'];
            } else {
                $formData['dob'] = null;
            }
        }

        $dataForModel = parent::convertFormDataForModel($formData);

        if (isset($dataForModel['email']) && ($dataForModel['email']['address'] == "")) {
            $dataForModel['email'] = null;
        }

        if (isset($dataForModel['phone']) && ($dataForModel['phone']['number'] == "")) {
            $dataForModel['phone'] = null;
        }

        if (isset($dataForModel['name']) && is_array($dataForModel['name']) && ($dataForModel['name']['first'] == "") && ($dataForModel['name']['last'] == "")) {
            $dataForModel['name'] = null;
        }

        return $dataForModel;
    }

    /**
     * Function to set the actor names for all actors associated with the current LPA as a data attribute
     *
     * @param array $actorNames
     */
    public function setExistingActorNamesData(array $actorNames)
    {
        $this->setAttribute('data-actor-names', json_encode($actorNames));
    }
}
