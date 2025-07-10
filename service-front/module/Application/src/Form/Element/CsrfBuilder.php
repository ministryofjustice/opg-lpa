<?php

namespace Application\Form\Validator;

use Laminas\ServiceManager\ServiceManager;

class CsrfBuilder
{
    public function __construct(private readonly ServiceManager $serviceManager)
    {
    }

    public function __invoke(string $name) : Csrf
    {
        return Cs
        // TODO: Implement __invoke() method.
    }
}