<?php

namespace Application\Form\Admin;

use Application\Form\AbstractForm;
use Zend\Validator\Between;

/**
 * For an admin to set the system message
 *
 * Class SystemMessageForm
 * @package Application\Form\Admin
 */
class PaymentSwitch extends AbstractForm
{
    public function init()
    {
        $this->setName('admin-payment-switch');

        $this->add([
            'name' => 'percentage',
            'type' => 'Number',
        ]);

        //  Add data to the input filter
        $this->addToInputFilter([
            'name'     => 'percentage',
            'filters'  => [
                [
                    'name' => 'Int'
                ],
            ],
            'required' => true,
            'validators' => [
                [
                    'name'    => 'Between',
                    'options' => [
                        'min' => 0,
                        'max' => 100,
                        'messages' => [
                            Between::NOT_BETWEEN => "Must be between 0 and 100",
                        ],
                    ],
                ],
            ],
        ]);

        parent::init();
    }
}
