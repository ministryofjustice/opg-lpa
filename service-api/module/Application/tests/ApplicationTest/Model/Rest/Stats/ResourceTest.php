<?php

namespace ApplicationTest\Model\Rest\Stats;

use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Stats\Entity;
use Application\Model\Rest\Stats\Resource;
use ApplicationTest\AbstractResourceTest;
use Mockery;
use MongoCollection;
use MongoDB\BSON\UTCDateTime as MongoDate;
use MongoDB\Driver\ReadPreference;

class ResourceTest extends AbstractResourceTest
{
    public function testGetIdentifier()
    {
        $resource = new Resource();
        $this->assertEquals('type', $resource->getIdentifier());
    }

    public function testGetName()
    {
        $resource = new Resource();
        $this->assertEquals('stats', $resource->getName());
    }

    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_COLLECTION, $resource->getType());
    }

    public function testFetch()
    {
        $generated = new MongoDate();

        $statsLpasCollection = Mockery::mock(MongoCollection::class);
        $statsLpasCollection->shouldReceive('setReadPreference');
        $statsLpasCollection->shouldReceive('findOne')
            ->withArgs([[], ['readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)]])
            ->andReturn(['generated' => $generated]);

        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withStatsLpasCollection($statsLpasCollection)->build();

        $entity = $resource->fetch('all');

        $this->assertEquals(new Entity(['generated' => date('d/m/Y H:i:s', $generated->toDateTime()->getTimestamp())]), $entity);

        $resourceBuilder->verify();
    }
}