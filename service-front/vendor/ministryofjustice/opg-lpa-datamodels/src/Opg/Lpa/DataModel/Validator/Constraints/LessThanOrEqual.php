<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Daniel Holmes <daniel@danielholmes.org>
 */
class LessThanOrEqual extends AbstractComparison
{
    public $message = 'must-be-less-than-or-equal:{{ compared_value }}';
}
