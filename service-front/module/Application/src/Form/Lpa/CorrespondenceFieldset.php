<?php

namespace Application\Form\Lpa;

use Zend\Form\Fieldset;
use Zend\Hydrator\ClassMethodsHydrator as ClassMethodsHydrator;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;

class CorrespondenceFieldset extends Fieldset
{
    /**
     * @param  null|int|string  $name    Optional name for the element
     * @param  array            $options Optional options for the element
     */
    public function __construct($name = null, $options = [])
    {
        parent::__construct('fieldset-correspondence', $options);

        $this->setHydrator(new ClassMethodsHydrator(false))
             ->setObject(new Correspondence());

        $this->add([
            'name' => 'contactByEmail',
            'type' => 'Checkbox',
            'options' => [
                'checked_value' => true,
                'unchecked_value' => false,
            ],
        ]);

        $this->add([
            'name' => 'contactByPhone',
            'type' => 'Checkbox',
            'options' => [
                'checked_value' => true,
                'unchecked_value' => false,
            ],
        ]);

        $this->add([
            'name' => 'contactByPost',
            'type' => 'Checkbox',
            'options' => [
                'checked_value' => true,
                'unchecked_value' => false,
            ],
        ]);

        $this->add([
            'name' => 'email-address',
            'type' => 'Email',
        ]);

        $this->add([
            'name' => 'phone-number',
            'type' => 'Text',
        ]);
    }

    public function setMessages($messages)
    {
        $this->messages = $messages;

        parent::setMessages($messages);
    }

    public function getMessages($elementName = null)
    {
        return $this->messages;
    }
}
