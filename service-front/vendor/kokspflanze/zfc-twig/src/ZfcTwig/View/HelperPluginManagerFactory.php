<?php

namespace ZfcTwig\View;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\View\Exception;
use ZfcTwig\ModuleOptions;

class HelperPluginManagerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return HelperPluginManager
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var ModuleOptions $options */
        $options        = $container->get(ModuleOptions::class);
        $managerOptions = $options->getHelperManager();
        $managerConfigs = isset($managerOptions['configs']) ? $managerOptions['configs'] : [];

        /** @var HelperPluginManager $viewHelper */
        //$viewHelper = $container->get('ViewHelperManager');
        $viewHelper = new HelperPluginManager($container, $managerOptions);

        foreach ($managerConfigs as $configClass) {
            if (is_string($configClass) && class_exists($configClass)) {
                /** @var ConfigInterface $config */
                $config = new $configClass;

                if (!$config instanceof ConfigInterface) {
                    throw new Exception\RuntimeException(
                        sprintf(
                            'Invalid service manager configuration class provided; received "%s",
                                expected class implementing %s',
                            $configClass,
                            ConfigInterface::class
                        )
                    );
                }

                $config->configureServiceManager($viewHelper);
            }
        }

        return $viewHelper;
    }

}
