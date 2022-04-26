<?php

declare(strict_types=1);

namespace Application\Form\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\FormInput;

class FormText extends FormInput
{
    /**
     * Attributes valid for the input tag type="text"
     * Includes Global Attribute inputmode
     *
     * @var array
     */
    protected $validTagAttributes = [
        'name'           => true,
        'autocomplete'   => true,
        'autofocus'      => true,
        'dirname'        => true,
        'disabled'       => true,
        'form'           => true,
        'list'           => true,
        'inputmode'      => true,
        'maxlength'      => true,
        'minlength'      => true,
        'pattern'        => true,
        'placeholder'    => true,
        'readonly'       => true,
        'required'       => true,
        'size'           => true,
        'type'           => true,
        'value'          => true,
    ];

    /**
     * Determine input type to use
     *
     * @param  ElementInterface $element
     * @return string
     */
    protected function getType(ElementInterface $element): string
    {
        return 'text';
    }
}
