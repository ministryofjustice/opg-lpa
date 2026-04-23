<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Service\Factory\TelemetryTracerFactory;
use MakeShared\Telemetry\Exporter\ExporterFactory;
use MakeShared\Telemetry\Tracer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TelemetryTracerFactoryTest extends TestCase
{
    public function testFactoryReturnsTracer(): void
    {
        $exporterFactory = $this->createMock(ExporterFactory::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            'config'               => [
                'telemetry' => [
                    'exporter' => [
                        'serviceName' => 'opg-lpa-front',
                    ],
                ],
            ],
            ExporterFactory::class => $exporterFactory,
        });

        $tracer = (new TelemetryTracerFactory())($container);

        $this->assertInstanceOf(Tracer::class, $tracer);
    }
}
