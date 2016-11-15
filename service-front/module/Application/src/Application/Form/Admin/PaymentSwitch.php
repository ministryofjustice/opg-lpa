<?php
namespace Application\Form\Admin;

use Zend\Validator;
use Application\Form\General\AbstractForm;

/**
 * For an admin to set the system message
 *
 * Class SystemMessageForm
 * @package Application\Form\Admin
 */
class PaymentSwitch extends AbstractForm {

    public function __construct( $formName = 'admin-payment-sqitch' ) {

        parent::__construct( $formName );

        //--- Form elements

        $this->add(array(
            'name' => 'percentage',
            'type' => 'Number',
        ));

        //--------------------------------
        
        $inputFilter = $this->getInputFilter();

        $inputFilter->add([
            'name'     => 'percentage',
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
                array('name' => 'Int'),
            ),
            'required' => true,
            'validators' => array(
                [
                    'name'    => 'Between',
                    'options' => [
                        'min' => 0, 'max' => 100,
                        'messages' => [
                            Validator\Between::NOT_BETWEEN => "Must be between 0 and 100",
                        ],
                    ],
                ],
            ),
        ]);
        
        $this->setInputFilter( $inputFilter );

    } // function

} // class
