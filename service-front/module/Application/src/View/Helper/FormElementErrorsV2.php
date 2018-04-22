<?php
namespace Application\View\Helper;

use Zend\Form\ElementInterface;

class FormElementErrorsV2 extends \Zend\Form\View\Helper\FormElementErrors
{
    public function __invoke(ElementInterface $element = null, array $attributes = array(), array $messageOverrideMap = [])
    {
        if (!$element) {
            return $this;
        }

        $this->setMessageOpenFormat('<span class="error-message text">');
        $this->setMessageCloseString('</span>');
        $this->setMessageSeparatorString('<br>');

        if (count($element->getMessages()) > 0) {
            
            $messages = $element->getMessages();
            
            foreach ($messages as $key => &$message) {
                if (array_key_exists($key, $messageOverrideMap)) {
                    $message = $messageOverrideMap[$key];
                }
            }
            
            $element->setMessages($messages);
            
            echo $this->render($element, $attributes);
            
        }

    }

}