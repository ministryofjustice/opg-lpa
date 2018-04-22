<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\Form\ElementInterface;
use Zend\Form\Form;

class FormLinkedErrorListV2 extends AbstractHelper
{
    /**
     * Iterate through all errors and output them.
     *
     * @param ElementInterface
     */
    public function __invoke(Form $form)
    {

        foreach( $form->getMessages() as $field=>$errors ){

            foreach( $errors as $error ){

                if( is_array( $error ) ){

                    foreach( $error as $subError ){
                        $this->outputError( $field, $subError );
                    }

                } else {
                    $this->outputError( $field, $error );
                }

            } // foreach

        } // foreach

    } // function

    /**
     * Output one error message
     *
     * @param $field
     * @param $error
     */
    private function outputError( $field, $error ){

        echo '<li><a href="#' . $field . '">';
        echo $error;
        echo '</a></li>';

    }
    
}