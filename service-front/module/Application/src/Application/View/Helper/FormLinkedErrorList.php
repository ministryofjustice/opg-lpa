<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\Form\ElementInterface;
use Zend\Form\Form;

class FormLinkedErrorList extends AbstractHelper
{
    /**
     * @param ElementInterface
     */
    public function __invoke(Form $form)
    {
        foreach ($form->getElements() as $element) {
            foreach ($element->getMessages() as $elementMessage) {
                echo '<li><a href="#' . $element->getAttribute('name') . '">';
                echo $element->getLabel() .' '. $elementMessage;
                echo '</a></li>';
            }
        }
    }
}