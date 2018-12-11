<?php

namespace Application\Form\View\Helper;

use Zend\Form\Element\MultiCheckbox;
use Zend\Form\Element\Radio;
use Zend\Form\LabelAwareInterface;
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

    /**
     * Render options
     *
     * To add a class to the div set $attributes['div-attributes']['class']
     *
     * @param  MultiCheckbox $element
     * @param  array         $options
     * @param  array         $selectedOptions
     * @param  array         $attributes
     * @return string
     */
    protected function renderOptions(
        MultiCheckbox $element,
        array $options,
        array $selectedOptions,
        array $attributes
    ) {
        $escapeHtmlHelper = $this->getEscapeHtmlHelper();
        $labelHelper      = $this->getLabelHelper();
        $globalLabelAttributes = [];
        $closingBracket   = $this->getInlineClosingBracket();

        // Setup div opening
        $divOpen = '<div>';

        if (isset($attributes['div-attributes']['class'])) {
            $divOpen = '<div class="' . $attributes['div-attributes']['class'] . '">';
        }

        // Setup label attributes common to all options
        if ($element instanceof LabelAwareInterface) {
            $globalLabelAttributes = $element->getLabelAttributes();
        }

        if (empty($globalLabelAttributes)) {
            $globalLabelAttributes = $this->labelAttributes;
        }

        $combinedMarkup = [];
        $count          = 0;

        foreach ($options as $key => $optionSpec) {
            $count++;
            if ($count > 1 && array_key_exists('id', $attributes)) {
                unset($attributes['id']);
            }

            $value           = '';
            $label           = '';
            $inputAttributes = $attributes;
            $labelAttributes = $globalLabelAttributes;

            if (isset($attributes['id'])) {
                $labelAttributes['for'] = $attributes['id'];
            }

            $selected        = (isset($inputAttributes['selected'])
                && $inputAttributes['type'] != 'radio'
                && $inputAttributes['selected']);
            $disabled        = (isset($inputAttributes['disabled']) && $inputAttributes['disabled']);

            if (is_scalar($optionSpec)) {
                $optionSpec = [
                    'label' => $optionSpec,
                    'value' => $key
                ];
            }

            if (isset($optionSpec['value'])) {
                $value = $optionSpec['value'];
            }
            if (isset($optionSpec['label'])) {
                $label = $optionSpec['label'];
            }
            if (isset($optionSpec['selected'])) {
                $selected = $optionSpec['selected'];
            }
            if (isset($optionSpec['disabled'])) {
                $disabled = $optionSpec['disabled'];
            }
            if (isset($optionSpec['label_attributes'])) {
                $labelAttributes = (isset($labelAttributes))
                    ? array_merge($labelAttributes, $optionSpec['label_attributes'])
                    : $optionSpec['label_attributes'];
            }
            if (isset($optionSpec['attributes'])) {
                $inputAttributes = array_merge($inputAttributes, $optionSpec['attributes']);
            }

            if (in_array($value, $selectedOptions)) {
                $selected = true;
            }

            $inputAttributes['value']    = $value;
            $inputAttributes['checked']  = $selected;
            $inputAttributes['disabled'] = $disabled;

            $input = sprintf(
                '<input %s%s',
                $this->createAttributesString($inputAttributes),
                $closingBracket
            );

            if (null !== ($translator = $this->getTranslator())) {
                $label = $translator->translate(
                    $label,
                    $this->getTranslatorTextDomain()
                );
            }

            if (! $element instanceof LabelAwareInterface || ! $element->getLabelOption('disable_html_escape')) {
                $label = $escapeHtmlHelper($label);
            }

            $markup  = $divOpen .
                $input .
                $labelHelper->openTag($labelAttributes) .
                $label .
                $labelHelper->closeTag() .
                '</div>';

            $combinedMarkup[] = $markup;
        }

        return implode($this->getSeparator(), $combinedMarkup);
    }
}
