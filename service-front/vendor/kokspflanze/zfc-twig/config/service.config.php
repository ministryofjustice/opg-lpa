<?php

use Twig\Environment;
use Twig\Loader;
use ZfcTwig\ModuleOptions;
use ZfcTwig\ModuleOptionsFactory;
use ZfcTwig\Twig;
use ZfcTwig\View;

return [
    'aliases' => [
        'ZfcTwigExtension'               => Twig\Extension::class,
        'ZfcTwigLoaderChain'             => Loader\ChainLoader::class,
        'ZfcTwigLoaderTemplateMap'       => Twig\MapLoader::class,
        'ZfcTwigLoaderTemplatePathStack' => Twig\StackLoader::class,
        'ZfcTwigRenderer'                => View\TwigRenderer::class,
        'ZfcTwigResolver'                => View\TwigResolver::class,
        'ZfcTwigViewHelperManager'       => View\HelperPluginManager::class,
        'ZfcTwigViewStrategy'            => View\TwigStrategy::class,
    ],

    'factories' => [
        Environment::class  => Twig\EnvironmentFactory::class,
        Loader\ChainLoader::class => Twig\ChainLoaderFactory::class,

        Twig\Extension::class => Twig\ExtensionFactory::class,
        Twig\MapLoader::class => Twig\MapLoaderFactory::class,

        Twig\StackLoader::class         => Twig\StackLoaderFactory::class,
        View\TwigRenderer::class        => View\TwigRendererFactory::class,
        View\TwigResolver::class        => View\TwigResolverFactory::class,
        View\HelperPluginManager::class => View\HelperPluginManagerFactory::class,
        View\TwigStrategy::class        => View\TwigStrategyFactory::class,

        ModuleOptions::class => ModuleOptionsFactory::class
    ]
];
