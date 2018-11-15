<?php

namespace Application\Form\View\Helper;

use Zend\Form\Element\Radio;
use Zend\Form\View\Helper\FormRadio as ZFFormRadioHelper;

class FormRadio extends ZFFormRadioHelper
{
    /**
     * This allows us to output a single Radio option from an Radio Element's available options.
     *
     * @param   Radio   $element
     * @param   string  $option
     * @param   array   $labelAttributes
     * @return  string
     */
    public function outputOption(Radio $element, $option, $labelAttributes = [])
    {
        $element = clone $element;

        $name = static::getName($element);

        $options = $element->getValueOptions();

        if (!isset($options[$option])) {
            return '';
        }

        $attributes         = $element->getAttributes();
        $attributes['name'] = $name;
        $attributes['type'] = $this->getInputType();
        $selectedOptions    = (array) $element->getValue();

        if (isset($options[$option]['value'])) {
            $attributes['id'] = $name . '-' . $options[$option]['value'];
        }

        $options = [
            $option => $options[$option],
        ];

        // Set label attributes
        $labelAttributes += $element->getLabelAttributes();
        $element->setLabelAttributes($labelAttributes);

        $rendered = $this->renderOptions($element, $options, $selectedOptions, $attributes);

        //  If applicable render a hidden element
        if ($element->useHiddenElement() || $this->useHiddenElement) {
            $rendered = $this->renderHiddenElement($element, $attributes) . $rendered;
        }

        return $rendered;
    }
}
