<?php

declare(strict_types=1);

namespace App\Form\SharedSpace;

use App\Form\AbstractForm;
use Laminas\Form\Element\Text;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class JoinSharedSpaceForm extends AbstractForm
{
    public function init(): void
    {
        $this->setName('joinSharedSpace');

        $this->add([
            'name'       => 'sharedSpaceName',
            'type'       => Text::class,
            'options' => [
                'label' => 'Shared space name'
            ],
            'attributes' => [
                'id'    => 'sharedSpaceName',
                'class' => 'govuk-input govuk-input--width-20',
            ],
        ]);

        $this->addToInputFilter([
            'name' => 'sharedSpaceName',
            'required' => true,
        ]);

        $this->add([
            'name'       => 'sharedSpaceAccessCode',
            'type'       => Text::class,
            'options' => [
                'label' => 'Your shared space access code'
            ],
            'attributes' => [
                'id'    => 'sharedSpaceAccessCode',
                'class' => 'govuk-input govuk-input--width-20 govuk-input--extra-letter-spacing',
            ],
        ]);

        $this->addToInputFilter([
            'name' => 'sharedSpaceAccessCode',
            'required' => true,
        ]);

        parent::init();
    }
}
