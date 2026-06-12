<?php

declare(strict_types=1);

namespace App\Form\Fieldset;

use Laminas\Form\Fieldset;

class Dob extends Fieldset
{
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

    public function getMessages($elementName = null): array
    {
        return $this->messages;
    }
}
