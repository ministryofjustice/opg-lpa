<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Form\View\Helper;

use Zend\Form\ElementInterface;

class FormRadio extends FormMultiCheckbox
{
    /**
     * Return input type
     *
     * @return string
     */
    protected function getInputType()
    {
        return 'radio';
    }

    /**
     * Get element name
     *
     * @param  ElementInterface $element
     * @return string
     */
    protected static function getName(ElementInterface $element)
    {
        return $element->getName();
    }

    /**
     * This allows us to output a single Radio option from an Radio Element's available options.
     *
     * @param ElementInterface $element
     * @param $option
     * @param array $labelAttributes
     * @return string
     */
    public function outputOption( ElementInterface $element, $option, $labelAttributes = array() ){

        $element = clone $element;

        $name = static::getName($element);

        $options = $element->getValueOptions();

        if( !isset($options[$option]) ){
            return '';
        }

        //---

        $element->setLabelAttributes( $element->getLabelAttributes() + $labelAttributes );

        //---

        $attributes         = $element->getAttributes();
        $attributes['name'] = $name;
        $attributes['type'] = $this->getInputType();
        $selectedOptions    = (array) $element->getValue();

        $options = array(
            "$option" => $options[$option],
        );

        $rendered = $this->renderOptions($element, $options, $selectedOptions, $attributes);

        // Render hidden element
        $useHiddenElement = method_exists($element, 'useHiddenElement') && $element->useHiddenElement()
            ? $element->useHiddenElement()
            : $this->useHiddenElement;

        if ($useHiddenElement) {
            $rendered = $this->renderHiddenElement($element, $attributes) . $rendered;
        }

        return $rendered;

    }

}
