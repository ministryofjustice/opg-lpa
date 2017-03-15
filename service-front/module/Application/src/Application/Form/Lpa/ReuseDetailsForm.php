<?php

namespace Application\Form\Lpa;

use Zend\Form\Element\MultiCheckbox;
use Zend\Form\Form;

class ReuseDetailsForm extends Form
{
    /**
     * ReuseDetailsForm constructor
     *
     * @param int|null|string $name
     * @param array $options
     */
    public function __construct($name, $options)
    {
        //  Trigger the parent constructor now
        parent::__construct($name, $options);

        //  Add the required inputs
        //  Use the custom reuse details input
        $this->add([
            'name' => 'reuse-details',
            'type' => 'Application\Form\Element\ReuseDetails',
            'required' => true,
            'options' => [
                'value_options' => [
                    'actorReuseDetails' => (array_key_exists('actorReuseDetails', $options) ? $options['actorReuseDetails'] : []),
                ],
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'Submit',
        ]);
    }

    /**
     * Simple function to indicate if we have a full selection of details to reuse
     *
     * @return bool
     */
    public function reusingPreviousLpaOptions()
    {
        $reuseDetails = $this->get('reuse-details');

        if ($reuseDetails instanceof MultiCheckbox) {
            return (count($reuseDetails->getValueOptions()) > 1);
        }

        return false;
    }
}
