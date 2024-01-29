<?php

namespace Application\Form\Lpa;

/**
 * @template T
 * @template-extends AbstractLpaForm<T>
 */

class ReuseDetailsForm extends AbstractLpaForm
{
    protected $formElements = [
        'reuse-details' => [
            'type'          => 'Laminas\Form\Element\Radio',
            'attributes' => [
                'id' => 'reuse-details',
                'div-attributes' => ['class' => 'multiple-choice'],
            ],
            'required'      => true,
            'error_message' => 'cannot-be-empty',
            'options'       => [
                'value_options' => [],
            ],
        ],
        'submit' => [
            'type' => 'Submit',
        ],
    ];

    /**
     * @param null|int|string $name Optional name for the element
     * @param array $options Optional options for the element
     */
    public function __construct($name = null, $options = [])
    {
        if (array_key_exists('actorReuseDetails', $options)) {
            $this->formElements['reuse-details']['options']['value_options'] =
                $this->createReuseDetailsOptions($options['actorReuseDetails']);

            unset($options['actorReuseDetails']);
        }

        parent::__construct($name, $options);
    }

    public function init()
    {
        $this->setName('form-reuse-details');
        $this->setAttribute('data-cy', 'form-reuse-details');

        $this->setUseInputFilterDefaults(false);

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    protected function validateByModel()
    {
        return [
            'isValid'  => true,
            'messages' => [],
        ];
    }

    /**
     * @param array $options
     * @return array
     */
    private function createReuseDetailsOptions($options)
    {
        $reuseDetailsValueOptions = [];

        foreach ($options as $idx => $actor) {
            $reuseDetailsValueOptions[] = [
                'label' => $actor['label'],
                'value' => $idx,
                'label_attributes' => [
                    'class' => 'text block-label flush--left',
                ],
            ];
        }

        // If there is more than one value option, add a "none of the above" option
        if (count($reuseDetailsValueOptions) > 1) {
            $reuseDetailsValueOptions[] = [
                'label' => 'None of the above - I want to add a new person',
                'value' => '-1',
                'label_attributes' => [
                    'class' => 'text block-label flush--left',
                ],
            ];
        }

        return $reuseDetailsValueOptions;
    }
}
