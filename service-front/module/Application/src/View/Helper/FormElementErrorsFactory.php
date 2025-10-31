<?php

namespace Application\View\Helper;

use Laminas\Form\View\Helper\FormElementErrors;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class FormElementErrorsFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return FormElementErrors
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $formElementErrors = new FormElementErrors();

        $formElementErrors->setMessageOpenFormat(
            '<span class="error-message text"><span class="visually-hidden">Error:</span>'
        );
        $formElementErrors->setMessageCloseString('</span>');
        $formElementErrors->setMessageSeparatorString('<br>');

        return $formElementErrors;
    }
}
