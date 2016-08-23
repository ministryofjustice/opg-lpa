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

        foreach( $form->getMessages() as $field=>$errors ){
            foreach( $errors as $error ){
                echo '<li><a href="#' . $field . '">';
                echo $error;
                echo '</a></li>';
            }
        }

    }
    
}