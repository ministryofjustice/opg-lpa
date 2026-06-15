<?php

declare(strict_types=1);

namespace App\Form\Lpa;

/**
 * @template T
 * @template-extends AbstractMainFlowForm<T>
 */
class BlankMainFlowForm extends AbstractMainFlowForm
{
    public function init()
    {
        $this->add([
            'name'       => 'submit',
            'type'       => 'Submit',
            'attributes' => [
                'value' => 'Save and continue',
                'class' => 'button',
            ],
        ]);

        parent::init();
    }

    protected function validateByModel()
    {
        return [
            'isValid'  => true,
            'messages' => [],
        ];
    }
}
