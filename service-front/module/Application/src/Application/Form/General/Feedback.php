<?php
namespace Application\Form\General;

use Zend\Validator;

/**
 * To send feedback to the OPG
 *
 * Class Feedback
 * @package Application\Form\General
 */
class Feedback extends AbstractForm {

    public function __construct( $formName = 'send-feedback' ){

        parent::__construct( $formName );

        //--- Form elements

        //--------------------------------

        $inputFilter = $this->getInputFilter();

        $this->setInputFilter( $inputFilter );

    } // function

} // class
