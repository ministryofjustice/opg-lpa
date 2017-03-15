<?php

namespace Application\Form\Element;

use Zend\Form\Element\Radio as ZFRadio;

class ReuseDetails extends ZFRadio
{
    /**
     * @param  array $options
     * @return MultiCheckbox
     */
    public function setValueOptions(array $options)
    {
        //  Intercept the actor reuse details data and set the value options
        if (array_key_exists('actorReuseDetails', $options)) {
            $reuseDetailsValueOptions = [];

            foreach ($options['actorReuseDetails'] as $idx => $actor) {
                $isTrust = (isset($actor['data']['type']) && $actor['data']['type'] == 'trust');
                $reuseDetailsValueOptions[] = $this->createValueOption($actor['label'], $idx, $isTrust);
            }

            //  If there is more than one value option then add a none of the above option also
            if (count($reuseDetailsValueOptions) > 1) {
                $reuseDetailsValueOptions[] = $this->createValueOption('None of the above', -1, false);
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
     * @param   boolean $isTrust
     * @return  array
     */
    private function createValueOption($label, $index, $isTrust)
    {
        return [
            'label'            => $label,
            'value'            => $index,
            'label_attributes' => [
                'class' => 'text block-label flush--left',
            ],
            'is-trust'         => $isTrust,
        ];
    }

    /**
     * Get the reuse details value options
     *
     * @param   bool    $trustOnly
     * @return  array
     */
    public function getReuseDetailsValueOptions($trustOnly = false)
    {
        $valueOptions = [];

        foreach ($this->getValueOptions() as $valueOption) {
            $isTrust = (isset($valueOption['is-trust']) && $valueOption['is-trust'] === true);

            if ($trustOnly && !$isTrust) {
                continue;
            }

            $valueOptions[$valueOption['value']] = $valueOption;
        }

        return $valueOptions;
    }
}
