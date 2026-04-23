<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Form\Error\FormLinkedErrors;
use Psr\Container\ContainerInterface;

class FormLinkedErrorsFactory
{
    public function __invoke(ContainerInterface $container): FormLinkedErrors
    {
        return new FormLinkedErrors();
    }
}
