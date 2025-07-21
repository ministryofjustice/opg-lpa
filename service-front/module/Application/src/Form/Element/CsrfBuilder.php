<?php

namespace Application\Form\Element;

use Application\Form\Validator\Csrf as CsrfValidator;
use Laminas\Form\Element\Csrf;
use Laminas\ServiceManager\ServiceManager;

class CsrfBuilder
{
    public function __construct(private readonly ServiceManager $serviceManager)
    {
    }

    public function __invoke(string $name) : Csrf
    {
        $csrfName = 'secret_' . md5($name);
        $csrf = new Csrf($csrfName);
        $csrfSalt = $this->serviceManager->get('config')['csrf']['salt'];
        $csrfValidator = $this->serviceManager->build(CsrfValidator::class, [
            [
                'name' => $csrf->getName(),
                'salt' => $csrfSalt,
            ]
        ] );

        $csrf->setCsrfValidator($csrfValidator);
        return $csrf;
    }
}