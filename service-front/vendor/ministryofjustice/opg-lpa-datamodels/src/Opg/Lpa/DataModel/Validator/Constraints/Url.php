<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Url extends SymfonyConstraints\Url
{
    use ValidatorPathTrait;

    public $message = 'This value is not a valid URL.';
    public $dnsMessage = 'The host could not be resolved.';

    public $protocols = [
        'http',
        'https'
    ];

    public $checkDNS = false;
}
