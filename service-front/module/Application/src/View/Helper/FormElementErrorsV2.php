<?php
namespace Application\View\Helper;

use Laminas\Form\ElementInterface;

class FormElementErrorsV2 extends \Laminas\Form\View\Helper\FormElementErrors
{
    /**
     * @return null|static
     */
    public function __invoke(ElementInterface $element = null, array $attributes = array(), array $messageOverrideMap = []): ?self
    {
        if (!$element) {
            return $this;
        }

        $this->setMessageOpenFormat('<span class="error-message text"><span class="visually-hidden">Error:</span>');
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
