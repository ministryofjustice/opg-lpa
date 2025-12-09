<?php

namespace Application\Form\General;

use Application\Form\AbstractCsrfForm;
use Application\Form\Validator\Phone;
use Application\Form\Validator\EmailAddress;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

/**
 * To send feedback to the OPG
 *
 * @template T
 * @template-extends AbstractCsrfForm<T>
 */
class FeedbackForm extends AbstractCsrfForm
{
    private $maxFeedbackLength = 2000;

    public function init()
    {
        $this->setName('send-feedback');

        $this->add([
            'name'    => 'rating',
            'type'    => 'Laminas\Form\Element\Radio',
            'attributes' => [
                'id' => 'rating',
                'div-attributes' => ['class' => 'multiple-choice']
            ],
            'options' => [
                'value_options' => [
                    'very-satisfied' => [
                        'label' => 'Very satisfied',
                        'value' => 'very-satisfied',
                        'attributes' => ['data-cy' => 'very-satisfied'],
                    ],
                    'satisfied' => [
                        'label' => 'Satisfied',
                        'value' => 'satisfied',
                        'attributes' => ['data-cy' => 'satisfied'],
                    ],
                    'neither-satisfied-or-dissatisfied' => [
                        'label' => 'Neither satisfied nor dissatisfied',
                        'value' => 'neither-satisfied-or-dissatisfied',
                        'attributes' => ['data-cy' => 'neither-satisfied-or-dissatisfied'],
                    ],
                    'dissatisfied' => [
                        'label' => 'Dissatisfied',
                        'value' => 'dissatisfied',
                        'attributes' => ['data-cy' => 'dissatisfied'],
                    ],
                    'very-dissatisfied' => [
                        'label' => 'Very dissatisfied',
                        'value' => 'very-dissatisfied',
                        'attributes' => ['data-cy' => 'very-dissatisfied'],
                    ],
                ],
                'disable_inarray_validator' => true,
            ],
        ]);

        $this->add([
            'name' => 'details',
            'type' => 'Textarea',
        ]);

        $this->add([
            'name' => 'email',
            'type' => 'Email',
        ]);

        $this->add([
            'name' => 'phone',
            'type' => 'Text',
        ]);

        //  Add data to the input filter
        $this->setUseInputFilterDefaults(false);

        $this->addToInputFilter([
            'name'          => 'rating',
            'error_message' => 'cannot-be-empty',
        ]);

        $this->addToInputFilter([
            'name'     => 'details',
            'filters'    => [
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ],
                ],
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => $this->maxFeedbackLength,
                        'messages' => [
                             StringLength::TOO_LONG => 'max-' . $this->maxFeedbackLength . '-chars',
                         ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'email',
            'required' => false,
            'validators' => [
                [
                    'name' => EmailAddress::class
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'phone',
            'required' => false,
            'validators' => [
                [
                    'name' => Phone::class
                ],
            ]
        ]);

        parent::init();
    }
}
