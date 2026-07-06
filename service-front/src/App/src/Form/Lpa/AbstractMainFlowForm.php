<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use App\Model\FormFlowChecker;
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
            $finalCheckAccessible = FormFlowChecker::isFinalCheckAccessible($this->lpa);
        }

        $this->formElements['save'] = [
            'type'       => 'Submit',
            'attributes' => [
                'value'   => ($finalCheckAccessible ? 'Save and return to final check' : 'Save and continue'),
                'class'   => 'govuk-button',
                'data-cy' => 'save',
            ],
        ];

        parent::init();
    }
}
