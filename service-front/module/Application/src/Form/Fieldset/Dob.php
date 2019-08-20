<?php

namespace Application\Form\Fieldset;

use Zend\Form\Fieldset;

class Dob extends Fieldset
{
    /**
     * @param  null|int|string  $name    Optional name for the element
     * @param  array            $options Optional options for the element
     */
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->add([
            'name' => 'day',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'month',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'year',
            'type' => 'Text',
        ]);
    }

    public function setMessages($messages)
    {
        $this->messages = $messages;

        return parent::setMessages($messages);
    }

    public function getMessages($elementName = null)
    {
        return $this->messages;
    }
}
