<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Daniel Holmes <daniel@danielholmes.org>
 */
class GreaterThan extends AbstractComparison
{
    public $message = 'This value should be greater than {{ compared_value }}.';
}
