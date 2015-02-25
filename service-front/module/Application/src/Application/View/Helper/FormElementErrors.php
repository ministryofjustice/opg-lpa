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
        
        $this->setMessageOpenFormat('<div class="group validation"><span class="validation-message">');
        $this->setMessageCloseString('</span></div>');
        $this->setMessageSeparatorString('<br>');
        
        echo '<p class="form-element-errors">';
        echo $this->render($element, $attributes);
        echo '</p>';
        
    }
    
}