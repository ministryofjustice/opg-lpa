<?php
namespace Application\Form\General;

/**
 * To send feedback to the OPG
 *
 * Class Feedback
 * @package Application\Form\General
 */
class FeedbackForm extends AbstractForm {

    public function __construct( $formName = 'send-feedback' ){

        parent::__construct( $formName );

        //--- Form elements

        //--------------------------------

        $inputFilter = $this->getInputFilter();

        $this->setInputFilter( $inputFilter );

    } // function

} // class
