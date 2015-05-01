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

    const MAX_MESSAGE_LENGTH = 8000;
    
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
            'required' => false,
            'validators' => array(
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => self::MAX_MESSAGE_LENGTH,
                        'messages' => [
                             StringLength::TOO_LONG => 'Please limit the message to ' . self::MAX_MESSAGE_LENGTH . ' chars.',
                         ],
                    ],
                ],
            ),
        ]);
        
        $this->setInputFilter( $inputFilter );

    } // function

} // class
