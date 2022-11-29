<?php

namespace Application\Form\Fieldset;

use Laminas\Form\Fieldset;
use Laminas\Hydrator\ClassMethodsHydrator as ClassMethodsHydrator;
use MakeShared\DataModel\Lpa\Document\Correspondence as CorrespondenceModel;

class Correspondence extends Fieldset
{
    /**
     * @param  null|int|string  $name    Optional name for the element
     * @param  array            $options Optional options for the element
     */
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->setHydrator(new ClassMethodsHydrator(false))
             ->setObject(new CorrespondenceModel());

        $this->add([
            'name' => 'contactByEmail',
            'type' => 'Checkbox',
            'options' => [
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
        ]);

        $this->add([
            'name' => 'contactByPhone',
            'type' => 'Checkbox',
            'options' => [
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
        ]);

        $this->add([
            'name' => 'contactByPost',
            'type' => 'Checkbox',
            'options' => [
                'checked_value' => '1',
                'unchecked_value' => '0',
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
        return $this;
    }

    public function getMessages($elementName = null): array
    {
        return $this->messages;
    }
}
