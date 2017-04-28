<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

abstract class AbstractComparison extends SymfonyConstraints\AbstractComparison
{
    use ValidatorPathTrait;
}
