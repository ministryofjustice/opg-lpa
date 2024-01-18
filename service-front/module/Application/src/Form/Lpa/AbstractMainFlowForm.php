<?php

namespace Application\Form\Lpa;

use Application\Model\FormFlowChecker;
use MakeShared\DataModel\Lpa\Lpa;

/**
 * @template T
 * @template-extends AbstractLpaForm<T>
 */

abstract class AbstractMainFlowForm extends AbstractLpaForm
{
    public function init()
    {
        $finalCheckAccessible = false;

        if ($this->lpa instanceof Lpa) {
            $flowChecker = new FormFlowChecker($this->lpa);
            $finalCheckAccessible = $flowChecker->finalCheckAccessible();
        }

        //  Add the submit button to the form elements
        $this->formElements['save'] = [
            'type'       => 'Submit',
            'attributes' => [
                'value' => ($finalCheckAccessible ? 'Save and return to final check' : 'Save and continue'),
                'class' => 'button',
                'data-cy' => 'save',
            ],
        ];

        parent::init();
    }
}
