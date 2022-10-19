<?php

namespace Application\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\FormElementErrors;

class FormElementErrorsV2 extends FormElementErrors
{
    public function __invoke(
        ElementInterface $element = null,
        array $attributes = [],
        array $messageOverrideMap = []
    ) {
        if (!$element) {
            return $this;
        }

        $this->setMessageOpenFormat(
            '<span class="error-message text"><span class="visually-hidden">Error:</span>'
        );
        $this->setMessageCloseString('</span>');
        $this->setMessageSeparatorString('<br>');

        if (count($element->getMessages()) > 0) {
            $messages = $element->getMessages();

            foreach ($messages as $key => $message) {
                if (array_key_exists($key, $messageOverrideMap)) {
                    $messages[$key] = $messageOverrideMap[$key];
                }
            }

            $element->setMessages($messages);

            return $this->render($element, $attributes);
        }

        return '';
    }
}
