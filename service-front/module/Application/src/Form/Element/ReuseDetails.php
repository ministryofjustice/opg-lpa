<?php

namespace Application\Form\Element;

use Zend\Form\Element\Radio as ZFRadio;

class ReuseDetails extends ZFRadio
{
    /**
     * @param  array $options
     * @return ReuseDetails
     */
    public function setValueOptions(array $options)
    {
        //  Intercept the actor reuse details data and set the value options
        if (array_key_exists('actorReuseDetails', $options)) {
            $reuseDetailsValueOptions = [];

            foreach ($options['actorReuseDetails'] as $idx => $actor) {
                $reuseDetailsValueOptions[] = $this->createValueOption($actor['label'], $idx);
            }

            //  If there is more than one value option then add a none of the above option also
            if (count($reuseDetailsValueOptions) > 1) {
                $reuseDetailsValueOptions[] = $this->createValueOption('None of the above - I want to add a new person', -1);
            }

            $options = $reuseDetailsValueOptions;

            unset($options['actorReuseDetails']);
        }

        parent::setValueOptions($options);

        return $this;
    }

    /**
     * Simple function to create consistent value option arrays
     *
     * @param   string  $label
     * @param   integer $index
     * @return  array
     */
    private function createValueOption($label, $index)
    {
        return [
            'label'            => $label,
            'value'            => $index,
            'label_attributes' => [
                'class' => 'text block-label flush--left',
            ],
        ];
    }
}
