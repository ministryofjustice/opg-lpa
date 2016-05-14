<?php
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
class Url extends SymfonyConstraints\Url
{
    use ValidatorPathTrait;

    public $message = 'This value is not a valid URL.';
    public $dnsMessage = 'The host could not be resolved.';
    public $protocols = array('http', 'https');
    public $checkDNS = false;
}
