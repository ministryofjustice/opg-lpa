<?php

namespace MakeShared\datamodel\validator\constraints;

use symfony\Component\Validator\Constraints as SymfonyConstraints;

class Regex extends SymfonyConstraints\Regex
{
    use ValidatorPathTrait;
}
