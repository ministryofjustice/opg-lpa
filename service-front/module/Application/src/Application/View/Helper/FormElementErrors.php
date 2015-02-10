<?php
namespace Application\View\Helper;

use Zend\Form\ElementInterface;

class FormElementErrors extends \Zend\Form\View\Helper\FormElementErrors
{
    public function __invoke(ElementInterface $element = null, array $attributes = array())
    {
        $this->setMessageOpenFormat('<span class="validation-message">');
        $this->setMessageCloseString('</span>');
        
        return parent::__invoke($element, $attributes);
    }
    
}