<?php

namespace ZfcTwig\Twig;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZfcTwig\ModuleOptions;

class StackLoaderFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return \Zend\View\Resolver\TemplatePathStack
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var ModuleOptions $options */
        $options = $container->get(ModuleOptions::class);

        /** @var $templateStack \Zend\View\Resolver\TemplatePathStack */
        $zfTemplateStack = $container->get('ViewTemplatePathStack');

        $templateStack = new StackLoader($zfTemplateStack->getPaths()->toArray());
        $templateStack->setDefaultSuffix($options->getSuffix());

        return $templateStack;
    }

}
