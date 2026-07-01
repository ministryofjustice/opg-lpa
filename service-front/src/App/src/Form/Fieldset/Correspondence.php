<?php

declare(strict_types=1);

namespace App\Form\Fieldset;

use Laminas\Form\Fieldset;
use Laminas\Hydrator\ClassMethodsHydrator;
use MakeShared\DataModel\Lpa\Document\Correspondence as CorrespondenceModel;

class Correspondence extends Fieldset
{
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->setHydrator(new ClassMethodsHydrator(false))
             ->setObject(new CorrespondenceModel());

        $this->add([
            'name' => 'contactByEmail',
            'type' => 'Checkbox',
            'attributes' => ['class' => 'govuk-checkboxes__input'],
            'options' => [
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
        ]);

        $this->add([
            'name' => 'contactByPhone',
            'type' => 'Checkbox',
            'attributes' => ['class' => 'govuk-checkboxes__input'],
            'options' => [
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
        ]);

        $this->add([
            'name' => 'contactByPost',
            'type' => 'Checkbox',
            'attributes' => ['class' => 'govuk-checkboxes__input'],
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

    /**
     * @param Traversable|array $messages
     *
     * @psalm-param array{contactByEmail: list{'error message'}} $messages
     */
    public function setMessages(iterable $messages)
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
