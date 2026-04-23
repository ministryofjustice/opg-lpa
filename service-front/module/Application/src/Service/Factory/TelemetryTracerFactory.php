<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use MakeShared\Telemetry\Exporter\ExporterFactory;
use MakeShared\Telemetry\Tracer;
use Psr\Container\ContainerInterface;

class TelemetryTracerFactory
{
    public function __invoke(ContainerInterface $container): Tracer
    {
        $telemetryConfig = $container->get('config')['telemetry'];

        return Tracer::create(
            $container->get(ExporterFactory::class),
            $telemetryConfig,
        );
    }
}
