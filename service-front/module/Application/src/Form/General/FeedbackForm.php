<?php

namespace Application\Form\General;

use Application\Form\AbstractCsrfForm;
use Zend\Validator\NotEmpty;
use Zend\Validator\StringLength;

/**
 * To send feedback to the OPG
 *
 * Class Feedback
 * @package Application\Form\General
 */
class FeedbackForm extends AbstractCsrfForm
{
    private $maxFeedbackLength = 2000;

    public function init()
    {
        $this->setName('send-feedback');

        $this->add([
            'name'    => 'rating',
            'type'    => 'Radio',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'options' => [
                'value_options' => [
                    'very-satisfied' => [
                        'label' => 'Very satisfied',
                        'value' => 'very-satisfied',
                        'label_attributes' => [
                            'for' => 'very_satisfied_radio',
                        ],
                        'attributes' => [
                            'id' => 'very_satisfied_radio',
                        ],
                    ],
                    'satisfied' => [
                        'label' => 'Satisfied',
                        'value' => 'satisfied',
                        'label_attributes' => [
                            'for' => 'satisfied_radio',
                        ],
                        'attributes' => [
                            'id' => 'satisfied_radio',
                        ],                        
                    ],
                    'neither-satisfied-or-dissatisfied' => [
                        'label' => 'Neither satisfied nor dissatisfied',
                        'value' => 'neither-satisfied-or-dissatisfied',
                        'label_attributes' => [
                            'for' => 'neither_satisfied_radio',
                        ],
                        'attributes' => [
                            'id' => 'neither_satisfied_radio',
                        ],                        
                    ],
                    'dissatisfied' => [
                        'label' => 'Dissatisfied',
                        'value' => 'dissatisfied',
                        'label_attributes' => [
                            'for' => 'dissatisfied_radio',
                        ],
                        'attributes' => [
                            'id' => 'dissatisfied_radio',
                        ],                        
                    ],
                    'very-dissatisfied' => [
                        'label' => 'Very dissatisfied',
                        'value' => 'very-dissatisfied',
                        'label_attributes' => [
                            'for' => 'very_dissatisfied_radio',
                        ],
                        'attributes' => [
                            'id' => 'very_dissatisfied_radio',
                        ],                        
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
                    'name' => 'Application\Form\Validator\EmailAddress'
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'phone',
            'required' => false,
        ]);

        parent::init();
    }
}
