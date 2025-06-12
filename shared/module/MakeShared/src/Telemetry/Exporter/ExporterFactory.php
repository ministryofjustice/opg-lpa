<?php

namespace MakeShared\Telemetry\Exporter;

use Laminas\ServiceManager\ServiceLocatorInterface;

class ExporterFactory
{
    public function __construct(private ServiceLocatorInterface $container)
    {
    }

    public function createLogExporter(): ExporterInterface
    {
        return $this->container->get(LogExporter::class);
    }

    public function createXRayExporter(string $host, int $port): ExporterInterface
    {
        return $this->container->build(XrayExporter::class, [$host,$port]);
    }
}