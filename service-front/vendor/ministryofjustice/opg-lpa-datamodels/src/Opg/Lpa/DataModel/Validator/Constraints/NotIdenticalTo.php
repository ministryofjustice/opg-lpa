<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Daniel Holmes <daniel@danielholmes.org>
 */
class NotIdenticalTo extends AbstractComparison
{
    public $message = 'This value should not be identical to {{ compared_value_type }} {{ compared_value }}.';
}
