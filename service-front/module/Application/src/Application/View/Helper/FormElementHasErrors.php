<?php
namespace Application\View\Helper;

use Zend\Form\ElementInterface;

class FormElementErrors extends \Zend\Form\View\Helper\FormElementErrors
{
    public function __invoke(ElementInterface $element = null, array $attributes = array())
    {
        if (!$element) {
            return $this;
        }

        if (count($element->getMessages()) > 0) {
            echo ' field-element-has-errors';
        }
        else{
            echo '';
        }

    }

}