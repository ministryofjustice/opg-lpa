<?php

namespace MakeSharedTest\Telemetry;

use MakeShared\Telemetry\Segment;
use Mockery;
use PHPUnit\Framework\TestCase;

class SegmentTest extends TestCase
{
    private function getEndTime($segment)
    {
        return json_decode(json_encode($segment), true)['end_time'];
    }

    public function testEnd()
    {
        $segment = new Segment('makeSharedUnitTest1', '1-581cf771-a006649127e371903a2de979');
        $segment->end();

        $this->assertIsFloat($this->getEndTime($segment), 'segment end time should be a float');
    }

    public function testEndAlreadyEnded()
    {
        $segment = new Segment('makeSharedUnitTest2', '1-581cf772-a006649127e371903a2de979');
        $segment->end();

        $endTime = $this->getEndTime($segment);

        $segment->end();

        $expectedEndTime = $this->getEndTime($segment);

        // end time shouldn't have changed, as segment was already
        // ended when second call to end() was made
        $this->assertEquals(
            $expectedEndTime,
            $endTime,
            'segment end time should not have changed on second call to end()'
        );
    }

    public function testEndWithChildren()
    {
        $parentSegment = new Segment('makeSharedUnitTest2', '1-581cf772-a006649127e371903a2de979');
        $childSegment1 = $parentSegment->addChild('childSegment1');
        $childSegment2 = $parentSegment->addChild('childSegment2');

        $parentSegment->end();

        $this->assertIsFloat($this->getEndTime($parentSegment));
        $this->assertIsFloat(
            $this->getEndTime($childSegment1),
            'ending a parent segment should end its children'
        );
        $this->assertIsFloat(
            $this->getEndTime($childSegment2),
            'ending a parent segment should end its children'
        );

        // check JSON encoding correctly places the subsegments and
        // relates them to the parent
        $parentArray = json_decode(json_encode($parentSegment), true);

        $this->assertEquals(2, count($parentArray['subsegments']));

        foreach ($parentArray['subsegments'] as $subSegment) {
            $this->assertEquals('subsegment', $subSegment['type']);
            $this->assertEquals($parentSegment->getId(), $subSegment['parent_id']);
        }
    }
}
