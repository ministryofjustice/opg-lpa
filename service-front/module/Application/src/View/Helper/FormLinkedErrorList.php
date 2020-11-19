<?php
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Laminas\Form\ElementInterface;
use Laminas\Form\Form;

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
                $label = $element->getLabel();
                if($label) {
                    echo $label . ' - '; 
                }
                echo $elementMessage;
                echo '</a></li>';
            }
        }
    }
}