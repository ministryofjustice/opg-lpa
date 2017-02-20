<?php
namespace Application\Form\Admin;

use Application\Form\General\AbstractForm;
use Zend\Validator;

/**
 * For an admin to set the system message
 *
 * Class SystemMessageForm
 * @package Application\Form\Admin
 */
class PaymentSwitch extends AbstractForm
{
    public function __construct($formName = null)
    {
        parent::__construct('admin-payment-switch');

        $this->add([
            'name' => 'percentage',
            'type' => 'Number',
        ]);

        $inputFilter = $this->getInputFilter();

        $inputFilter->add([
            'name'     => 'percentage',
            'filters'  => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
                ['name' => 'Int'],
            ],
            'required' => true,
            'validators' => [
                [
                    'name'    => 'Between',
                    'options' => [
                        'min' => 0, 'max' => 100,
                        'messages' => [
                            Validator\Between::NOT_BETWEEN => "Must be between 0 and 100",
                        ],
                    ],
                ],
            ],
        ]);

        $this->setInputFilter($inputFilter);
    }
}
