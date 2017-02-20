<?php
namespace Application\Form\Admin;

use Application\Form\General\AbstractForm;
use Zend\Validator\StringLength;

/**
 * For an admin to set the system message
 *
 * Class SystemMessageForm
 * @package Application\Form\Admin
 */
class SystemMessageForm extends AbstractForm
{
    private $maxMessageLength = 8000;

    public function __construct($formName = null)
    {
        parent::__construct('admin-system-message');

        $this->add([
            'name' => 'message',
            'type' => 'Textarea',
        ]);

        $inputFilter = $this->getInputFilter();

        $inputFilter->add([
            'name'     => 'message',
            'filters'  => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'required' => false,
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => $this->maxMessageLength,
                        'messages' => [
                             StringLength::TOO_LONG => 'Please limit the message to ' . $this->maxMessageLength . ' chars.',
                         ],
                    ],
                ],
            ],
        ]);

        $this->setInputFilter($inputFilter);
    }
}
