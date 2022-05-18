<?php

namespace Application\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\FormElementErrors;

class FormElementErrorsV2 extends FormElementErrors
{
    public function __invoke(
        ElementInterface $element = null,
        array $attributes = array(),
        array $messageOverrideMap = []
    ) {
        if (!$element) {
            return $this;
        }

        $this->setMessageOpenFormat('<span class="error-message text"><span class="visually-hidden">Error:</span>');
        $this->setMessageCloseString('</span>');
        $this->setMessageSeparatorString('<br>');

        if (count($element->getMessages()) > 0) {
            $messages = $element->getMessages();

            foreach ($messages as $key => &$message) {
                // Work-around for issue where we erroneously get multiple error messages
                // for certain fields, e.g. company name (see LPAL-325). If the error
                // message is set to '' for a field when calling the
                // formErrorTextExchange() twig macro, this prevents that error
                // from being displayed.
                if (array_key_exists($key, $messageOverrideMap) && $message !== '') {
                    $message = $messageOverrideMap[$key];
                }
            }
            $element->setMessages($messages);

            return $this->render($element, $attributes);
        }

        return '';
    }
}
