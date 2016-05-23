<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\Form\ElementInterface;
use Zend\Form\Form;

class FormLinkedErrorListV2 extends AbstractHelper
{
    /**
     * @param ElementInterface
     */
    public function __invoke(Form $form)
    {
        foreach ($form->getFieldsets() as $fieldset) {
            foreach ($fieldset->getMessages() as $elementMessage) {
                echo '<li><a href="#' . $fieldset->getAttribute('name') . '">';
                echo $elementMessage;
                echo '</a></li>';
            }
            $this->showErrors($fieldset);
        }
        $this->showErrors($form);
    }
    
    private function showErrors($formOrFieldset) {
        foreach ($formOrFieldset->getElements() as $element) {
            foreach ($element->getMessages() as $elementMessage) {
                echo '<li><a href="#' . $element->getAttribute('name') . '">';
                echo $elementMessage;
                echo '</a></li>';
            }
        }
    }
}