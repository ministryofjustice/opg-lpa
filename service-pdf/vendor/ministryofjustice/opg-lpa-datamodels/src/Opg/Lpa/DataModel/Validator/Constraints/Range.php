<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @api
 */
class Range extends SymfonyConstraints\Range
{
    use ValidatorPathTrait;

    const INVALID_VALUE_ERROR = 1;
    const BEYOND_RANGE_ERROR = 2;
    const BELOW_RANGE_ERROR = 3;

    protected static $errorNames = array(
        self::INVALID_VALUE_ERROR => 'INVALID_VALUE_ERROR',
        self::BEYOND_RANGE_ERROR => 'BEYOND_RANGE_ERROR',
        self::BELOW_RANGE_ERROR => 'BELOW_RANGE_ERROR',
    );

    public $minMessage = 'must-be-greater-than-or-equal:{{ limit }}';
    public $maxMessage = 'must-be-less-than-or-equal:{{ limit }}';
    public $invalidMessage = 'expected-type:number';

}
