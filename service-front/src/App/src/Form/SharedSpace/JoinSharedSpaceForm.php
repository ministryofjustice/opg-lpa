<?php

declare(strict_types=1);

namespace App\Form\SharedSpace;

use App\Form\AbstractForm;
use Laminas\Form\Element\Text;
use Laminas\Validator\Regex;

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
                'label' => 'Joining code',
                'hint'  => 'Joining codes are 8 digits long',
            ],
            'attributes' => [
                'id'    => 'sharedSpaceAccessCode',
                'class' => 'govuk-input govuk-input--width-20 govuk-input--extra-letter-spacing',
            ],
        ]);

        $this->addToInputFilter([
            'name' => 'sharedSpaceAccessCode',
            'required' => true,
            'validators' => [
                ['name' => Regex::class, 'options' => ['pattern' => '/^\d{8}$/']],
            ],
        ]);

        parent::init();
    }
}
