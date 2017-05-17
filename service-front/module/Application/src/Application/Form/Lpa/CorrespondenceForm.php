<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Correspondence;

class CorrespondenceForm extends AbstractActorForm
{
    protected $formElements = [
        'correspondence' => [
            'type' => 'Application\Form\Lpa\CorrespondenceFieldset',
            'options' => [
                'checked_value' => true,
                'unchecked_value' => false,
            ],
            'validators' => [
                [
                    'name' => 'Application\Form\Validator\Correspondence',
                ]
            ],
        ],
        'submit' => [
            'type' => 'Submit',
        ],
    ];

    public function init()
    {
        $this->setName('form-correspondence');

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    protected function validateByModel()
    {
        $error = ['correspondence' => []];

        $correspondent = new Correspondence([
            'contactByPost' => (bool)$this->data['correspondence']['contactByPost'],
            'contactByInWelsh' => (bool)$this->data['correspondence']['contactInWelsh'],
        ]);

        if ($this->data['correspondence']['contactByPhone'] == "1") {
            if (empty($this->data['correspondence']['phone-number'])) {
                $error['correspondence']['contactByPhone'] = ["Please enter the correspondent's phone number"];
            } else {
                $correspondent->phone = [ 'number' => $this->data['correspondence']['phone-number'] ];
            }
        }

        if ($this->data['correspondence']['contactByEmail'] == "1") {
            if (empty($this->data['correspondence']['email-address'])) {
                $error['correspondence']['contactByEmail'] = ["Please enter the correspondent's email address"];
            } else {
                $correspondent->email = [ 'address' => $this->data['correspondence']['email-address'] ];
            }
        }

        $validation = $correspondent->validate(['contactByPost', 'contactInWelsh', 'email', 'phone']);

        $messages = [];

        if ($validation->hasErrors()) {
            $errors = $this->modelValidationMessageConverter($validation);

            //  The following 2 if statements are a hack to ensure human readable error messages are show.
            //  The present our computer -> human method does not support this FieldSet use case.
            if (is_array($errors) && isset($errors['phone-number']) && is_array($errors['phone-number'])) {
                foreach ($errors['phone-number'] as $key => $message) {
                    if ($message == 'invalid-phone-number') {
                        $errors['phone-number'][$key] = 'Invalid phone number';
                    }
                }
            }

            if (is_array($errors) && isset($errors['email-address']) && is_array($errors['email-address'])) {
                foreach ($errors['email-address'] as $key => $message) {
                    if ($message == 'invalid-email-address') {
                        $errors['email-address'][$key] = 'Invalid email address';
                    }
                }
            }

            $messages = array_merge($error, ['correspondence' => $errors]);
        } elseif (count($error['correspondence']) != 0) {
            $messages = $error;
        }

        return [
            'isValid'  => !$validation->hasErrors(),
            'messages' => $messages,
        ];
    }
}
