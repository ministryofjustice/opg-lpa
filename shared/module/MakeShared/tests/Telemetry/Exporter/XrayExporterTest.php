<?php

namespace MakeSharedTest\Telemetry\Exporter;

use Hamcrest\Matchers;
use MakeShared\Logging\SimplerLogger;
use MakeShared\Telemetry\Exporter\XrayExporter;
use MakeShared\Telemetry\Segment;
use Mockery;
use PHPUnit\Framework\TestCase;

class XrayExporterTest extends TestCase
{
    public function testPayloadTooLarge()
    {
        $logger = Mockery::mock(SimplerLogger::class);
        $logger->shouldReceive('err')
            ->withArgs([
                Matchers::startsWith('Segment too large to export')
            ])
            ->andReturn()
            ->once();

        $exporter = new XrayExporter('localhost', 2000, $logger);

        $segment = new Segment('foo', 'bar');
        for ($i = 0; $i < 1000; $i++) {
            $segment->addChild("segment{$i}");
        }

        $json = json_encode($segment);

        $this->assertTrue(strlen($json) > XrayExporter::MAX_PAYLOAD_LEN);

        $exporter->export($segment);
    }
}
