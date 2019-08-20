<?php

namespace ApplicationTest\Form\View\Helper;

use Application\Form\View\Helper\FormMultiCheckbox;
use Zend\Form\Element\MultiCheckbox;

class TestableFormMultiCheckbox extends FormMultiCheckbox
{
    public function callRenderOptions(
        MultiCheckbox $element,
        array $options,
        array $selectedOptions,
        array $attributes
    ): string {
        return $this->renderOptions($element, $options, $selectedOptions, $attributes);
    }
}
