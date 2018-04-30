<?php

namespace ZfcTwig\Twig;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZfcTwig\ModuleOptions;

class MapLoaderFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return MapLoader
     * @throws \Twig\Error\LoaderError
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var ModuleOptions $options */
        $options = $container->get(ModuleOptions::class);

        /** @var \Zend\View\Resolver\TemplateMapResolver */
        $zfTemplateMap = $container->get('ViewTemplateMapResolver');

        $templateMap = new MapLoader();
        foreach ($zfTemplateMap as $name => $path) {
            if ($options->getSuffix() == pathinfo($path, PATHINFO_EXTENSION)) {
                $templateMap->add($name, $path);
            }
        }

        return $templateMap;
    }

}