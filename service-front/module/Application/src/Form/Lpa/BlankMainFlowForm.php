<?php

namespace Application\Form\Lpa;

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

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    protected function validateByModel()
    {
        return [
            'isValid'  => true,
            'messages' => [],
        ];
    }
}
