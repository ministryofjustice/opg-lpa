<?php

namespace Application\Form\Lpa;

class ReuseDetailsForm extends AbstractForm
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
            'type' => 'Zend\Form\Element\Submit',
        ],
    ];

    /**
     * ReuseDetailsForm constructor
     *
     * @param int|null|string $name
     * @param array $options
     */
    public function __construct($name, $options)
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
        parent::init();

        $this->setUseInputFilterDefaults(false);

        $inputFilter = $this->getInputFilter();

        $inputFilter->add(array(
            'name'          => 'reuse-details',
            'required'      => true,
            'error_message' => 'cannot-be-empty',
        ));
    }

    /**
     * Validate form input data through model validators.
     *
     * @return [isValid => bool, messages => [<formElementName> => string, ..]]
     */
    public function validateByModel()
    {
        return [
            'isValid'  => true,
            'messages' => [],
        ];
    }
}
