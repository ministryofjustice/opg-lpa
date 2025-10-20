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
        $exporter = $this->container->get(LogExporter::class);
        $exporter->setLogger($this->container->get('Logger'));
        return $exporter;
    }

    public function createXRayExporter(string $host, int $port): ExporterInterface
    {
        $exporter = $this->container->build(XrayExporter::class, [$host,$port]);
        $exporter->setLogger($this->container->get('Logger'));
        return $exporter;
    }
}
