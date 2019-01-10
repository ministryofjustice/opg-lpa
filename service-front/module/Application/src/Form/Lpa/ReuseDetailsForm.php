<?php

namespace Application\Form\Lpa;

class ReuseDetailsForm extends AbstractLpaForm
{
    protected $formElements = [
        'reuse-details' => [
            'type'          => 'Application\Form\Element\ReuseDetails',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
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
     * @param  null|int|string  $name    Optional name for the element
     * @param  array            $options Optional options for the element
     */
    public function __construct($name = null, $options = [])
    {
        //  Extract the value options data now
        if (array_key_exists('actorReuseDetails', $options)) {
            $this->formElements['reuse-details']['options']['value_options'] = [
                'actorReuseDetails' => $options['actorReuseDetails'],
            ];
        }

        parent::__construct($name, $options);
    }

    public function init()
    {
        $this->setName('form-reuse-details');

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
}
