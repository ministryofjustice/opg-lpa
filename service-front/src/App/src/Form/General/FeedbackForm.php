<?php

declare(strict_types=1);

namespace App\Form\General;

use App\Form\AbstractForm;
use App\Form\Validator\EmailAddress as EmailAddressValidator;
use App\Form\Validator\Phone as PhoneValidator;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class FeedbackForm extends AbstractForm
{
    private int $maxFeedbackLength = 2000;

    public function init()
    {
        $this->setName('send-feedback');

        $this->add([
            'name'    => 'rating',
            'type'    => 'Laminas\Form\Element\Radio',
            'attributes' => [
                'id'             => 'rating',
                'class'          => 'govuk-radios__input',
                'div-attributes' => ['class' => 'govuk-radios__item'],
            ],
            'options' => [
                'value_options' => [
                    'very-satisfied' => [
                        'label'      => 'Very satisfied',
                        'value'      => 'very-satisfied',
                        'attributes' => ['data-cy' => 'very-satisfied'],
                    ],
                    'satisfied' => [
                        'label'      => 'Satisfied',
                        'value'      => 'satisfied',
                        'attributes' => ['data-cy' => 'satisfied'],
                    ],
                    'neither-satisfied-or-dissatisfied' => [
                        'label'      => 'Neither satisfied nor dissatisfied',
                        'value'      => 'neither-satisfied-or-dissatisfied',
                        'attributes' => ['data-cy' => 'neither-satisfied-or-dissatisfied'],
                    ],
                    'dissatisfied' => [
                        'label'      => 'Dissatisfied',
                        'value'      => 'dissatisfied',
                        'attributes' => ['data-cy' => 'dissatisfied'],
                    ],
                    'very-dissatisfied' => [
                        'label'      => 'Very dissatisfied',
                        'value'      => 'very-dissatisfied',
                        'attributes' => ['data-cy' => 'very-dissatisfied'],
                    ],
                ],
                'disable_inarray_validator' => true,
            ],
        ]);

        $this->add(['name' => 'details', 'type' => 'Textarea']);
        $this->add(['name' => 'email', 'type' => 'Email']);
        $this->add(['name' => 'phone', 'type' => 'Text']);

        $this->setUseInputFilterDefaults(false);

        $this->addToInputFilter([
            'name'          => 'rating',
            'error_message' => 'cannot-be-empty',
        ]);

        $this->addToInputFilter([
            'name'       => 'details',
            'validators' => [
                [
                    'name'                   => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [NotEmpty::IS_EMPTY => 'cannot-be-empty'],
                    ],
                ],
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max'      => $this->maxFeedbackLength,
                        'messages' => [StringLength::TOO_LONG => 'max-' . $this->maxFeedbackLength . '-chars'],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'email',
            'required' => false,
            'filters'  => [['name' => 'StringToLower']],
            'validators' => [['name' => EmailAddressValidator::class]],
        ]);

        $this->addToInputFilter([
            'name'       => 'phone',
            'required'   => false,
            'validators' => [['name' => PhoneValidator::class]],
        ]);

        parent::init();
    }
}
