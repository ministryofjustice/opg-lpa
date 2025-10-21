<?php

namespace Application\Form\View\Helper;

use Laminas\Form\Element\MultiCheckbox;
use Laminas\Form\Element\Radio;
use Laminas\Form\LabelAwareInterface;
use Laminas\Form\View\Helper\FormRadio as LaminasFormRadioHelper;

class FormRadio extends LaminasFormRadioHelper
{
    /**
     * This allows us to output a single Radio option from a Radio Element's available options.
     *
     * @param Radio $element
     * @param string $option
     * @param array $labelAttributes
     * @return string
     */
    public function outputOption(Radio $element, $option, $labelAttributes = [])
    {
        $element = clone $element;

        $name = static::getName($element);

        $options = $element->getValueOptions();

        if (!isset($options[$option])) {
            return '';
        }

        $attributes = $element->getAttributes();
        $attributes['name'] = $name;
        $attributes['type'] = $this->getInputType();
        $selectedOptions = (array) $element->getValue();

        if (isset($options[$option]['value'])) {
            $attributes['id'] = $name . '-' . $options[$option]['value'];
        } else {
            $attributes['id'] = $name . '-' . $option;
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
            $element->setAttributes($attributes);
            $rendered = $this->renderHiddenElement($element) . $rendered;
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
    ): string {
        $escapeHtmlHelper = $this->getEscapeHtmlHelper();
        $labelHelper = $this->getLabelHelper();
        $globalLabelAttributes = [];
        $closingBracket = $this->getInlineClosingBracket();

        // Setup label attributes common to all options
        if ($element instanceof LabelAwareInterface) {
            $globalLabelAttributes = $element->getLabelAttributes();
        }

        if (empty($globalLabelAttributes)) {
            $globalLabelAttributes = $this->labelAttributes;
        }

        $combinedMarkup = [];
        $count = 0;

        foreach ($options as $key => $optionSpec) {
            // If multiple options are being rendered, unset the id after the
            // first option; if the ID is given we want that ID on the first
            // radio button (option) so that error messages shown in the error
            // summary are linked to that button
            if ($count > 0 && array_key_exists('id', $attributes)) {
                unset($attributes['id']);
            }

            $count++;

            $value = '';
            $label = '';
            $inputAttributes = $attributes;
            $labelAttributes = $globalLabelAttributes;

            $selected = (isset($inputAttributes['selected'])
                && $inputAttributes['type'] != 'radio'
                && $inputAttributes['selected']);
            $disabled = (isset($inputAttributes['disabled']) && $inputAttributes['disabled']);

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

            $inputAttributes['value'] = $value;
            $inputAttributes['data-cy'] = $element->getName() . '-' . $value;
            $inputAttributes['checked'] = $selected;
            $inputAttributes['disabled'] = $disabled;
            $inputAttributes['id'] = isset($attributes['id']) ?
                $attributes['id'] :
                $element->getName() . '-' . $value;

            $labelAttributes['for'] = $inputAttributes['id'];

            $input = sprintf(
                '<input %s%s',
                $this->createAttributesString($inputAttributes),
                $closingBracket
            );

            if (! $element instanceof LabelAwareInterface || ! $element->getLabelOption('disable_html_escape')) {
                $label = $escapeHtmlHelper($label);
            }

            // Setup wrapping div opening
            $divAttributes = isset($attributes['div-attributes']) ? $attributes['div-attributes'] : [];

            // If this option has it's own div attributes add them in
            if (isset($optionSpec['div-attributes'])) {
                $divAttributes = array_merge($divAttributes, $optionSpec['div-attributes']);
            }

            $divOpen = '<div>';

            if (count($divAttributes)) {
                $divOpen = '<div ' . $this->createAttributesString($divAttributes) . '>';
            }

            $markup = $divOpen .
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
