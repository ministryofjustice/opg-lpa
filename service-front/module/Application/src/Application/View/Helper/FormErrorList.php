<?php
namespace Application\View\Helper;

use Zend\Form\View\Helper\AbstractHelper;

class FormErrorList extends AbstractHelper
{
    public function __invoke()
    {
        if (count($this->view->form->getMessages()) > 0) {
        ?>
            <div class="validation-summary group" role="alert" aria-labelledby="error-heading" tabindex="-1">
                <h1 id="error-heading">There was a problem submitting the form</h1>
                <p>Because of the following problems:</p>
                <ol>
                    <?php
                    $this->view->formLinkedErrorList($this->view->form);
    
                    if (property_exists($this->view, 'error')) {
                        echo '<li><a href="">';
                        switch($this->view->error){
                            default:
                                echo $this->escapehtml( $this->view->error );
                        }
                        echo '</a></li>';
                    }
                    ?>
                </ol>
            </div>
       <?php
       }
    }
}