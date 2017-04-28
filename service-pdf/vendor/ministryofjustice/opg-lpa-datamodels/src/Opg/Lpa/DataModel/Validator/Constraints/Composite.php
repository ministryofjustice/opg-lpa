<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

abstract class Composite extends SymfonyConstraints\Composite
{
    use ValidatorPathTrait;

}
