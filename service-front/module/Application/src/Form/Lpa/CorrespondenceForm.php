<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Correspondence;

class CorrespondenceForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'contactInWelsh' => [
            'type' => 'Radio',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'required' => true,
            'options' => [
                'value_options' => [
                    'english' => [
                        'value' => '0',
                        'label' => 'English',
                        'label_attributes' => [
                            'class' => 'block-label',
                        ],
                    ],
                    'welsh' => [
                        'value' => '1',
                        'label' => 'Cymraeg',
                        'label_attributes' => [
                            'class' => 'block-label',
                        ],
                    ],
                ],
            ],
        ],
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
        //  Set the form data in a correspondent model so it can be validated
        $correspondenceData = $this->data['correspondence'];

        $correspondent = new Correspondence([
            'contactByPost' => (bool)$correspondenceData['contactByPost'],
            'contactByInWelsh' => (bool)$this->data['contactInWelsh'],
        ]);

        if ($correspondenceData['contactByPhone'] == '1') {
            $correspondent->phone = [
                'number' => $correspondenceData['phone-number']
            ];
        }

        if ($correspondenceData['contactByEmail'] == '1') {
            $correspondent->email = [
                'address' => $correspondenceData['email-address']
            ];
        }

        $validation = $correspondent->validate([
            'contactByPost',
            'contactInWelsh',
            'email',
            'phone'
        ]);

        $messages = [];

        //  If validation failed then get the error messages
        if ($validation->hasErrors()) {
            $messages = $this->modelValidationMessageConverter($validation);

            //  The following 2 if statements are a hack to ensure human readable error messages are show.
            //  The present our computer -> human method does not support this FieldSet use case.
            if (is_array($messages)) {
                //  Define the message mappings
                $messageMappings = [
                    'email-address' => [
                        'cannot-be-blank'       => 'Enter the correspondent\'s email address',
                        'invalid-email-address' => 'Enter a valid email address',
                    ],
                    'phone-number' => [
                        'cannot-be-blank'       => 'Enter the correspondent\'s phone number',
                        'invalid-phone-number'  => 'Enter a valid phone number',
                    ],
                ];

                //  Loop through the messages and try to translate any messages
                foreach ($messages as $fieldName => &$fieldMessages) {
                    if (array_key_exists($fieldName, $messageMappings)) {
                        foreach ($fieldMessages as $fieldMessageKey => $fieldMessage) {
                            if (array_key_exists($fieldMessage, $messageMappings[$fieldName])) {
                                $fieldMessages[$fieldMessageKey] = $messageMappings[$fieldName][$fieldMessage];
                            }
                        }
                    }
                }
            }
        }

        return [
            'isValid'  => !$validation->hasErrors(),
            'messages' => [
                'correspondence' => $messages,
            ],
        ];
    }
}
