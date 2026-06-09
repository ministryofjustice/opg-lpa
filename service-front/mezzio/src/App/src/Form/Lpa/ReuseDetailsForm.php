<?php

declare(strict_types=1);

namespace App\Form\Lpa;

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
                'id'             => 'reuse-details',
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

    public function __construct($name = null, $options = [])
    {
        // Handle case where Laminas InvokableFactory passes options as the first argument
        if (is_array($name) && empty($options)) {
            $options = $name;
            $name    = null;
        }

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

    protected function validateByModel()
    {
        return [
            'isValid'  => true,
            'messages' => [],
        ];
    }

    private function createReuseDetailsOptions(array $options): array
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
