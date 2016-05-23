<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class Existence extends Composite
{
    public $constraints = array();

    public function getDefaultOption()
    {
        return 'constraints';
    }

    protected function getCompositeOption()
    {
        return 'constraints';
    }
}
