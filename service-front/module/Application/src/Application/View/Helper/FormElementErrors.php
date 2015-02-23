<?php
namespace Application\View\Helper;

use Zend\Form\ElementInterface;

class FormElementErrors extends \Zend\Form\View\Helper\FormElementErrors
{
    public function __invoke(ElementInterface $element = null, array $attributes = array())
    {
        echo '<p class="form-element-errors">';
        
        $this->setMessageOpenFormat('<span class="validation-message">');
        $this->setMessageCloseString('</span>');
        $this->setMessageSeparatorString('<br>');
        
        return parent::__invoke($element, $attributes);
        echo '</p>';
    }
    
}