<?php

declare(strict_types=1);

namespace ApplicationTest\Form\View\Helper;

use Application\Form\View\Helper\FormMultiCheckbox;
use Laminas\Form\Element\MultiCheckbox;

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

    public function setViewForTesting(): void
    {
        $this->view = "Set To Something Non-Null To Allow Unit Testing";
    }
}
