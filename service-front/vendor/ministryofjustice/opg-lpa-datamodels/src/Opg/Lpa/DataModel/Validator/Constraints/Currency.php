<?php
namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Miha Vrhovnik <miha.vrhovnik@pagein.si>
 *
 * @api
 */
class Currency extends SymfonyConstraints\Currency
{
    use ValidatorPathTrait;

    public $message = 'This value is not a valid currency.';
}
