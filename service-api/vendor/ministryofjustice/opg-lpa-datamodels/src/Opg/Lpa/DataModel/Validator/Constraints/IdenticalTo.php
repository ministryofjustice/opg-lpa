<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Daniel Holmes <daniel@danielholmes.org>
 */
class IdenticalTo extends AbstractComparison
{
    public $message = 'This value should be identical to {{ compared_value_type }} {{ compared_value }}.';
}
