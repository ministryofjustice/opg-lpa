<?php

namespace MakeSharedTest\Telemetry;

use MakeShared\Constants;
use MakeShared\Logging\SimpleLogger;
use MakeShared\Telemetry\Exporter\LogExporter;
use MakeShared\Telemetry\Exporter\XrayExporter;
use MakeShared\Telemetry\Segment;
use MakeShared\Telemetry\Tracer;
use Mockery;
use PHPUnit\Framework\TestCase;

class TracerTest extends TestCase
{
    private $config = [
        'exporter' => [
            'serviceName' => 'makeSharedUnitTest',
        ]
    ];

    public function setUp(): void
    {
        $_SERVER = [];
    }

    public function testCreateNoConfigMakesConsoleExporter()
    {
        $tracer = Tracer::create($this->config);
        $this->assertInstanceOf(LogExporter::class, $tracer->getExporter());
    }

    public function testCreateWithConfigMakesXrayExporter()
    {
        $config['exporter'] = array_merge($this->config['exporter'], [
            'host' => 'localhost',
            'port' => '2000',
        ]);

        $tracer = Tracer::create($config);

        $this->assertInstanceOf(XrayExporter::class, $tracer->getExporter());
    }

    public function testStartStopRootSegmentFromServerEnv()
    {
        $tracer = Tracer::create($this->config);

        $_SERVER = [];
        $_SERVER[Constants::X_TRACE_ID_HEADER_NAME] =
            'Root=1-63a17088-02b1471a787d91f21767c8f8;Parent=1234567891123456;Sampled=1';

        $rootSegment = $tracer->startRootSegment();

        $this->assertEquals($rootSegment, $tracer->getRootSegment());
        $this->assertEquals($rootSegment->getId(), $tracer->getCurrentSegmentId());
        $this->assertEquals('1234567891123456', $rootSegment->getParentSegmentId());
        $this->assertTrue($rootSegment->sampled);

        // this exercises the console exporter
        $tracer->stopRootSegment();
    }

    public function testStartRootSegmentAlreadyStarted()
    {
        $tracer = Tracer::create($this->config);

        $headers = [];
        $headers[Constants::X_TRACE_ID_HEADER_NAME] =
            'Root=1-63a17088-02b1471a787d91f21767c8f8;Sampled=1';

        $rootSegment = $tracer->startRootSegment($headers);
        $anotherRootSegment = $tracer->startRootSegment($headers);

        $this->assertEquals(null, $anotherRootSegment);
        $this->assertEquals($rootSegment->getId(), $tracer->getCurrentSegmentId());
    }

    public function testStartRootSegmentNoRootInHeader()
    {
        $tracer = Tracer::create($this->config);

        $headers = [];
        $headers[Constants::X_TRACE_ID_HEADER_NAME] =
            '1-63a17088-02b1471a787d91f21767c8f8;Parent=1234567891123456;Sampled=1';

        $rootSegment = $tracer->startRootSegment();

        $this->assertEquals(null, $rootSegment);
        $this->assertEquals(null, $tracer->getCurrentSegmentId());
    }

    public function testStartSegment()
    {
        $tracer = Tracer::create($this->config);

        $headers = [];
        $headers[Constants::X_TRACE_ID_HEADER_NAME] =
            'Root=1-63a17088-02b1471a787d91f21767c8f8;Sampled=1';

        $rootSegment = $tracer->startRootSegment($headers);
        $nextSegment = $tracer->startSegment('foo');

        $this->assertEquals($rootSegment->getId(), $nextSegment->getParentSegmentId());
        $this->assertTrue($rootSegment->sampled);
        $this->assertTrue($nextSegment->sampled);
    }

    public function testStartSegmentRootNotStarted()
    {
        $tracer = Tracer::create($this->config);

        $nextSegment = $tracer->startSegment('foo');

        $this->assertEquals(null, $nextSegment);
        $this->assertEquals(null, $tracer->getCurrentSegmentId());
    }

    public function testStopSegmentRootNotStarted()
    {
        $tracer = Tracer::create($this->config);

        $tracer->stopSegment();

        $this->assertEquals(null, $tracer->getCurrentSegmentId());
    }

    public function testStopRootSegmentRootNotStarted()
    {
        $tracer = Tracer::create($this->config);

        $tracer->stopRootSegment();

        $this->assertEquals(null, $tracer->getCurrentSegmentId());
    }

    // Need to ensure as we start/stop segments within each other, current always
    // points to the most-recently opened segment;
    //
    // We create this structure in this test:
    //
    // root [
    //   segment1 [
    //     segment2 [
    //     ]
    //   ]
    // ]
    //
    // Testing start/stop methods and where "current" points to within this structure
    public function testSegmentNesting()
    {
        $exporter = Mockery::mock(XrayExporter::class);
        $tracer = new Tracer('makeSharedUnitTest', $exporter, new SimpleLogger());

        $headers = [];
        $headers[Constants::X_TRACE_ID_HEADER_NAME] =
            'Root=1-63a17088-02b1471a787d91f21767c8f8;Sampled=1';

        $rootSegment = $tracer->startRootSegment($headers);

        // we expect the root segment to be exported when stopRootSegment() is invoked
        $exporter->shouldReceive('export')->with($rootSegment);

        $this->assertEquals($tracer->getCurrentSegmentId(), $rootSegment->getId());

        $segment1 = $tracer->startSegment('segment1');

        $this->assertEquals($rootSegment->getId(), $segment1->getParentSegmentId());
        $this->assertEquals($tracer->getCurrentSegmentId(), $segment1->getId());

        $segment2 = $tracer->startSegment('segment2');

        $this->assertEquals($segment1->getId(), $segment2->getParentSegmentId());
        $this->assertEquals($tracer->getCurrentSegmentId(), $segment2->getId());

        // stop segment2
        $tracer->stopSegment();

        $this->assertEquals($tracer->getCurrentSegmentId(), $segment1->getId());

        // stop segment1
        $tracer->stopSegment();

        $this->assertEquals($tracer->getCurrentSegmentId(), $rootSegment->getId());

        // stop root segment
        $tracer->stopRootSegment();
    }

    public function testXTraceIdHeaderWithCurrentSegment()
    {
        $tracer = Tracer::create($this->config);

        // before we have a segment started in the tracer, the X-Trace-Id header
        // is null
        $this->assertEquals(null, $tracer->getTraceHeaderToForward());

        $headers = [];
        $headers[Constants::X_TRACE_ID_HEADER_NAME] =
            'Root=1-63a17088-02b1471a787d91f21767c8f8;Sampled=1';

        $rootSegment = $tracer->startRootSegment($headers);

        // current segment is $rootSegment, so the expected X-Trace-Id
        // header should include the root segment's ID as the Parent flag
        $parentId = $rootSegment->getId();
        $expectedHeader = "Root=1-63a17088-02b1471a787d91f21767c8f8;Parent=$parentId;Sampled=1";

        $this->assertEquals($expectedHeader, $tracer->getTraceHeaderToForward());
    }
}
