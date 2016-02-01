<?php
namespace Application\Form\General;

use Zend\Validator\NotEmpty;
use Zend\Validator\StringLength;
use Zend\Validator\InArray;
/**
 * To send feedback to the OPG
 *
 * Class Feedback
 * @package Application\Form\General
 */
class FeedbackForm extends AbstractForm {

    const MAX_FEEDBACK_LENGTH = 2000;
    
    public function __construct( $formName = 'send-feedback' ){

        parent::__construct( $formName );

        //--- Form elements
        
        $valueOptions = [
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
                        'value' => 'very-dissatisfied',
                    ],
                ];

        $this->add(array(
            'name' => 'rating',
            'type' => 'Radio',
            'options'   => [
                'value_options' => $valueOptions,
                'disable_inarray_validator' => true,
            ],
        ));
        
        $this->add(array(
            'name' => 'details',
            'type' => 'Textarea',
        ));
        
        $this->add(array(
            'name' => 'email',
            'type' => 'Email',
        ));
        
        //--------------------------------

        $inputFilter = $this->getInputFilter();

        $inputFilter->add([
            'name'     => 'rating',
            'error_message' => 'cannot-be-empty',
        ]);
        
        $inputFilter->add([
            'name'     => 'details',
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators' => [
                [
                    'name'    => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ],
                ],
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => self::MAX_FEEDBACK_LENGTH,
                        'messages' => [
                             StringLength::TOO_LONG => 'max-' . self::MAX_FEEDBACK_LENGTH . '-chars',
                         ],
                    ],
                ],
            ],
        ]);
        
        $inputFilter->add(array(
            'name'     => 'email',
            'required' => false,
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators' => [
                [
                    'name'    => 'EmailAddress',
                ],
            ],
        ));
        
        $this->setInputFilter( $inputFilter );

    } // function

} // class
