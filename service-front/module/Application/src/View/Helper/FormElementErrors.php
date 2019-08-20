<?php
namespace Application\View\Helper;

use Zend\Form\ElementInterface;

class FormElementErrors extends \Zend\Form\View\Helper\FormElementErrors
{
    public function __invoke(ElementInterface $element = null, array $attributes = array(), array $messageOverrideMap = [])
    {
        if (!$element) {
            return $this;
        }

        $this->setMessageOpenFormat('<div class="group validation"><span class="validation-message">');
        $this->setMessageCloseString('</span></div>');
        $this->setMessageSeparatorString('<br>');

        if (count($element->getMessages()) > 0) {
            
            $messages = $element->getMessages();
            
            foreach ($messages as $key => &$message) {
                if (array_key_exists($key, $messageOverrideMap)) {
                    $message = $messageOverrideMap[$key];
                }
            }
            
            $element->setMessages($messages);
            
            echo '<div class="form-element-errors">';
            echo $this->render($element, $attributes);
            echo '</div>';

        }

    }

}