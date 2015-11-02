<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Daniel Holmes <daniel@danielholmes.org>
 */
class NotEqualTo extends AbstractComparison
{
    public $message = 'This value should not be equal to {{ compared_value }}.';
}
