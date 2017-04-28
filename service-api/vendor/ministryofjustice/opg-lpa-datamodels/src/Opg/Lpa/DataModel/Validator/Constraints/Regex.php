<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Regex extends SymfonyConstraints\Regex
{
    use ValidatorPathTrait;

    public $message = 'invalid-regex-match';
    public $pattern;
    public $htmlPattern;
    public $match = true;

    public function getDefaultOption()
    {
        return 'pattern';
    }

    public function getRequiredOptions()
    {
        return ['pattern'];
    }
}
