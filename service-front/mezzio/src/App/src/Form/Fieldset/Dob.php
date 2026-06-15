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

    /**
     * @param string[][] $messages
     *
     * @psalm-param array{day?: list{'Value is required'}, year?: list{'Invalid year'}} $messages
     */
    public function setMessages(array $messages)
    {
        $this->messages = $messages;

        return parent::setMessages($messages);
    }

    /**
     * @param null|string $elementName
     *
     * @psalm-param 'year'|null $elementName
     */
    public function getMessages(string|null $elementName = null): array
    {
        return $this->messages;
    }
}
