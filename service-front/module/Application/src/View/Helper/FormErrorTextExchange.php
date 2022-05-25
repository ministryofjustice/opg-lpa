<?php

namespace Application\View\Helper;

use Laminas\Form\Form;
use Laminas\View\Helper\AbstractHelper;

class FormErrorTextExchange extends AbstractHelper
{
    /**
     * Catch-all transformations, ignorant of field name
     */
    /** @var array - Common Generic Map */
    private $commonMap = [];

    /**
     * Generic transformations for named fields
     */
    /** $var array - Common Field Map */
    private $commonFieldMap = [];

    /**
     * Look at each element message on the form. If a transform message exists
     * in the override map then replace the message with its override. If no
     * override message exists, see if there is a transformation in the common map.
     *
     * The override map is merged with a generic override map which provides
     * messages for common field names.
     *
     * @param Form $form
     * @return Form
     */
    public function __invoke($form, $overrideMap)
    {
        $overrideMap = array_merge_recursive(
            $this->commonFieldMap,
            $overrideMap
        );

        $elements = $form->getElements();

        foreach ($form->getFieldsets() as $fieldset) {
            foreach ($fieldset->getElements() as $element) {
                $elements[] = $element;
            }
            $elements[] = $fieldset;
        }

        foreach ($elements as $element) {
            $name = $element->getName();

            if (array_key_exists($name, $overrideMap)) {
                $elementMap = $overrideMap[$name];
            } else {
                $elementMap = [];
            }

            $messages = $element->getMessages();

            foreach ($messages as &$elementMessage) {
                if (is_string($elementMessage) || is_numeric($elementMessage)) {
                    if (array_key_exists($elementMessage, $elementMap)) {
                        $elementMessage = $elementMap[$elementMessage];
                    } elseif (array_key_exists($elementMessage, $this->commonMap)) {
                        $elementMessage = $this->commonMap[$elementMessage];
                    }
                }
            }

            $element->setMessages($messages);
        }

        return $form;
    }
}
