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

        $this->add(array(
            'name' => 'how-would-you-rate',
            'type' => 'Radio',
            'options'   => [
                'value_options' => [
                    'very-satisfied' => [
                            'value' => 'very-satisfied',
                    ],
                    'satisfied' => [
                            'value' => 'satisfied',
                    ],
                    'neither-satisfied-or-dissatisfied' => [
                        'value' => 'neither-satisfied-or-dissatisfied',
                    ],
                    'dissatisfied' => [
                        'value' => 'dissatisfied',
                    ],
                    'very-dissatisfied' => [
                        'value' => 'very-issatisfied',
                    ],
                ],
            ],
        ));
        
        $this->add(array(
            'name' => 'details',
            'type' => 'Textarea',
        ));
        
        $this->add(array(
            'name' => 'email',
            'type' => 'Text',
        ));
        
        //--------------------------------

        $inputFilter = $this->getInputFilter();

        $this->setInputFilter( $inputFilter );

    } // function

} // class
