<?php
namespace Application\Form\Admin;

use Zend\Validator\NotEmpty;
use Zend\Validator\StringLength;
use Application\Form\General\AbstractForm;

/**
 * For an admin to set the system message
 *
 * Class SystemMessageForm
 * @package Application\Form\Admin
 */
class SystemMessageForm extends AbstractForm {

    const MAX_MESSAGE_LENGTH = 2000;
    
    public function __construct( $formName = 'admin-system-message' ) {

        parent::__construct( $formName );

        //--- Form elements

        $this->add(array(
            'name' => 'message',
            'type' => 'Textarea',
        ));

        //--------------------------------
        
        $inputFilter = $this->getInputFilter();

        $inputFilter->add([
            'name'     => 'message',
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
                [
                    'name'    => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'No message was entered.',
                        ],
                    ],
                ],
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => self::MAX_MESSAGE_LENGTH,
                        'messages' => [
                             StringLength::TOO_LONG => 'Please limit your feedback to ' . self::MAX_MESSAGE_LENGTH . ' chars.',
                         ],
                    ],
                ],
            ),
        ]);
        
        $this->setInputFilter( $inputFilter );

    } // function

} // class
