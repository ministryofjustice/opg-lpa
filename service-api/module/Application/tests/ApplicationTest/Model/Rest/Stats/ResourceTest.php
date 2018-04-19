<?php

namespace ApplicationTest\Model\Rest\Stats;

use Application\Model\Rest\Stats\Entity;
use Application\Model\Rest\Stats\Resource;
use ApplicationTest\AbstractResourceTest;
use DateTime;
use Mockery;
use MongoDB\Collection as MongoCollection;
use MongoDB\Driver\ReadPreference;

class ResourceTest extends AbstractResourceTest
{
    public function testFetch()
    {
        $generated = date('d/m/Y H:i:s', (new DateTime())->getTimestamp());

        $statsLpasCollection = Mockery::mock(MongoCollection::class);
        $statsLpasCollection->shouldReceive('setReadPreference');
        $statsLpasCollection->shouldReceive('findOne')
            ->withArgs([[], ['readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)]])
            ->andReturn(['generated' => $generated]);

        $resource = new Resource($statsLpasCollection);

        $entity = $resource->fetch('all');

        $this->assertEquals(new Entity(['generated' => $generated]), $entity);
    }
}