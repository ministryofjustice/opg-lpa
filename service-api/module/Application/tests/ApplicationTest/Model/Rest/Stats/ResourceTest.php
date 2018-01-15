<?php

namespace ApplicationTest\Model\Rest\Stats;

use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Stats\Entity;
use Application\Model\Rest\Stats\Resource;
use ApplicationTest\AbstractResourceTest;
use DateTime;
use Mockery;
use MongoDB\Collection as MongoCollection;
use MongoDB\Driver\ReadPreference;

class ResourceTest extends AbstractResourceTest
{
    /**
     * @var Resource
     */
    private $resource;

    protected function setUp()
    {
        parent::setUp();

        $this->resource = new Resource($this->lpaCollection);

        $this->resource->setLogger($this->logger);

        $this->resource->setAuthorizationService($this->authorizationService);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('type', $this->resource->getIdentifier());
    }

    public function testGetName()
    {
        $this->assertEquals('stats', $this->resource->getName());
    }

    public function testGetType()
    {
        $this->assertEquals(AbstractResource::TYPE_COLLECTION, $this->resource->getType());
    }

    public function testFetch()
    {
        $generated = date('d/m/Y H:i:s', (new DateTime())->getTimestamp());

        $statsLpasCollection = Mockery::mock(MongoCollection::class);
        $statsLpasCollection->shouldReceive('setReadPreference');
        $statsLpasCollection->shouldReceive('findOne')
            ->withArgs([[], ['readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)]])
            ->andReturn(['generated' => $generated]);

        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withStatsLpasCollection($statsLpasCollection)->build();

        $entity = $resource->fetch('all');

        $this->assertEquals(new Entity(['generated' => $generated]), $entity);

        $resourceBuilder->verify();
    }
}