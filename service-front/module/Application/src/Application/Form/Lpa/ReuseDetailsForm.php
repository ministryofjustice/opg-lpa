<?php

namespace Application\Form\Lpa;

class ReuseDetailsForm extends AbstractLpaForm
{
    protected $formElements = [
        'reuse-details' => [
            'type'      => 'Application\Form\Element\ReuseDetails',
            'required'  => true,
            'options'   => [
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

        parent::__construct('form-reuse-details', $options);
    }

    public function init()
    {
        $this->setUseInputFilterDefaults(false);

        //  Add data to the input filter
        $this->addToInputFilter([
            'name'          => 'reuse-details',
            'required'      => true,
            'error_message' => 'cannot-be-empty',
        ]);

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    public function validateByModel()
    {
        return [
            'isValid'  => true,
            'messages' => [],
        ];
    }
}
