<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use App\Form\Validator\Correspondence as CorrespondenceValidator;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\PhoneNumber;
use MakeShared\DataModel\Lpa\Document\Correspondence;

/**
 * @template T
 * @template-extends AbstractMainFlowForm<T>
 */
class CorrespondenceForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'contactInWelsh' => [
            'type'     => 'Radio',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'required' => true,
            'options'  => [
                'value_options' => [
                    'english' => [
                        'value' => '0',
                        'label' => 'English',
                        'label_attributes' => ['class' => 'block-label'],
                    ],
                    'welsh' => [
                        'value' => '1',
                        'label' => 'Cymraeg',
                        'label_attributes' => ['class' => 'block-label'],
                    ],
                ],
            ],
        ],
        'correspondence' => [
            'type'    => 'App\Form\Fieldset\Correspondence',
            'options' => [
                'checked_value'   => '1',
                'unchecked_value' => '0',
            ],
            'validators' => [
                [
                    'name' => CorrespondenceValidator::class,
                ],
            ],
        ],
    ];

    public function init()
    {
        $this->setName('form-correspondence');
        $this->setAttribute('data-cy', 'form-correspondence');
        parent::init();
    }

    /**
     * @return ((array|mixed)[]|bool)[]
     *
     * @psalm-return array{isValid: bool, messages: array{correspondence: array<never, never>|mixed}}
     */
    protected function validateByModel()
    {
        $correspondenceData = $this->data['correspondence'];

        $correspondent = new Correspondence([
            'contactByPost'      => (bool)$correspondenceData['contactByPost'],
            'contactByInWelsh'   => (bool)$this->data['contactInWelsh'],
        ]);

        if ($correspondenceData['contactByPhone'] == '1') {
            $correspondent->setPhone(new PhoneNumber([
                'number' => $correspondenceData['phone-number'],
            ]));
        }

        if ($correspondenceData['contactByEmail'] == '1') {
            $correspondent->setEmail(new EmailAddress([
                'address' => $correspondenceData['email-address'],
            ]));
        }

        $validation = $correspondent->validate([
            'contactByPost',
            'contactInWelsh',
            'email',
            'phone',
        ]);

        $messages = [];

        if ($validation->hasErrors()) {
            $messages = $this->modelValidationMessageConverter($validation);

            if (is_array($messages)) {
                $messageMappings = [
                    'email-address' => [
                        'cannot-be-blank'       => 'Enter the correspondent\'s email address',
                        'invalid-email-address' => 'Enter a valid email address',
                    ],
                    'phone-number' => [
                        'cannot-be-blank'      => 'Enter the correspondent\'s phone number',
                        'invalid-phone-number' => 'Enter a valid phone number',
                    ],
                ];

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
